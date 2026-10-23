#!/usr/bin/env python3
"""Terapkan daftar revisi naskah ke file Word (.docx) sebagai Track Changes.

Setiap butir revisi di file JSON mencari teks di dalam SATU paragraf, lalu:
  - bila ada "ganti": teks lama ditandai terhapus dan teks baru ditandai
    tersisip (Track Changes), sehingga bisa di-Accept/Reject di Word;
  - bila ada "catatan": dipasang komentar Word pada teks tersebut
    (untuk revisi yang harus dikerjakan manual, mis. gambar ulang).
Format asli (font, ukuran, tebal/miring) dipertahankan.

Pemakaian:
  pip install lxml
  python patch_naskah.py naskah.docx revisi-naskah.json
  python patch_naskah.py naskah.docx revisi-naskah.json -o hasil.docx --penulis "Wan Salma"
  python patch_naskah.py naskah.docx revisi-naskah.json --uji        (cek saja, tanpa menulis)

Opsi lain:
  --langsung        ganti tanpa Track Changes
  --tanpa-komentar  abaikan semua "catatan"
  --pertama         bila teks ditemukan di beberapa tempat, pakai yang pertama
  --hanya ID,ID     terapkan butir tertentu saja
  --laporan F.csv   simpan laporan per butir

Format JSON:
  {"revisi": [{"id": "2.9-01", "lokasi": "2.9 hlm. 30",
               "cari": "awal kalimat…akhir kalimat", "ganti": "teks baru",
               "catatan": "instruksi manual", "semua": false}]}
  - "…" (elipsis) di "cari" berarti teks apa saja di paragraf yang sama.
  - *teks* di "ganti" ditulis miring; \\* untuk tanda bintang biasa.
  - "semua": true mengganti setiap kemunculan (untuk istilah).

File asli tidak pernah ditimpa.
"""

from __future__ import annotations

import argparse
import csv
import json
import re
import sys
import zipfile
from copy import deepcopy
from dataclasses import dataclass, field
from datetime import datetime, timezone
from pathlib import Path

try:
    from lxml import etree
except ImportError:
    sys.exit("Butuh pustaka lxml. Jalankan: pip install lxml")

W_NS = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
XML_NS = "http://www.w3.org/XML/1998/namespace"
REL_NS = "http://schemas.openxmlformats.org/package/2006/relationships"
CT_NS = "http://schemas.openxmlformats.org/package/2006/content-types"
COMMENTS_REL = "http://schemas.openxmlformats.org/officeDocument/2006/relationships/comments"
COMMENTS_CT = "application/vnd.openxmlformats-officedocument.wordprocessingml.comments+xml"

TEXT_PARTS = ("word/document.xml", "word/footnotes.xml", "word/endnotes.xml")

BARRIER = "\x00"
FIELD = "\x01"


def w(tag: str) -> str:
    return f"{{{W_NS}}}{tag}"


CONTAINERS = {w("hyperlink"), w("ins"), w("smartTag"), w("customXml"), w("sdt"), w("sdtContent"), w("moveTo")}
DELETED = {w("del"), w("moveFrom")}
TEXT_CHILDREN = {w("t"), w("tab"), w("lastRenderedPageBreak"), w("noBreakHyphen"), w("softHyphen")}
FIELD_CHILDREN = {w("fldChar"), w("instrText"), w("lastRenderedPageBreak")}

NORMALIZE = str.maketrans({
    "‘": "'", "’": "'", "‚": "'", "‛": "'",
    "“": '"', "”": '"', "„": '"',
    " ": " ", " ": " ", " ": " ", " ": " ", "\t": " ",
    "‐": "-", "‑": "-", "–": "-",
    "₀": "0", "₁": "1", "₂": "2", "₃": "3", "₄": "4",
    "₅": "5", "₆": "6", "₇": "7", "₈": "8", "₉": "9",
    "²": "2", "³": "3",
})


@dataclass
class Segment:
    """Satu run dalam paragraf: teks biasa, bagian field (sitasi), atau penghalang."""

    run: etree._Element
    kind: str
    chars: list[str]
    start: int

    @property
    def end(self) -> int:
        return self.start + len(self.chars)


@dataclass
class Revision:
    id: str
    cari: str
    lokasi: str = ""
    ganti: str | None = None
    catatan: str | None = None
    semua: bool = False
    status: str = ""
    detail: str = ""
    applied: int = 0


@dataclass
class Match:
    part: str
    paragraph: etree._Element
    start: int
    end: int
    snippet: str = ""


@dataclass
class Session:
    author: str
    date: str
    tracked: bool
    next_change_id: int
    comments: list[tuple[int, str]] = field(default_factory=list)
    next_comment_id: int = 0


def run_kind(run: etree._Element) -> tuple[str, list[str]]:
    children = [child for child in run if child.tag != w("rPr")]
    tags = {child.tag for child in children}
    if tags <= TEXT_CHILDREN:
        chars: list[str] = []
        for child in children:
            if child.tag == w("t"):
                chars.extend(child.text or "")
            elif child.tag == w("tab"):
                chars.append("\t")
            elif child.tag == w("noBreakHyphen"):
                chars.append("‑")
        return "text", chars
    if tags <= FIELD_CHILDREN:
        return "field", [FIELD]
    return "barrier", [BARRIER]


def iter_runs(container: etree._Element):
    """Run dalam urutan dokumen; teks yang sudah dihapus (w:del) dilewati."""
    for child in container:
        if child.tag == w("r"):
            yield child
        elif child.tag in DELETED:
            continue
        elif child.tag in CONTAINERS:
            yield from iter_runs(child)
        elif child.tag == w("pPr"):
            continue
        elif "".join(child.itertext()).strip():
            yield child


def paragraph_model(paragraph: etree._Element) -> tuple[list[Segment], str]:
    segments: list[Segment] = []
    position = 0
    for element in iter_runs(paragraph):
        if element.tag == w("r"):
            kind, chars = run_kind(element)
        else:
            kind, chars = "barrier", [BARRIER]
        segments.append(Segment(element, kind, chars, position))
        position += len(chars)
    text = "".join("".join(segment.chars) for segment in segments)
    return segments, text


def compile_pattern(cari: str) -> re.Pattern[str]:
    def literal(part: str) -> str:
        tokens = re.split(r"(\s+)", part)
        return "".join(" +" if token.isspace() else re.escape(token) for token in tokens if token)

    parts = cari.translate(NORMALIZE).split("\u2026")
    if len(parts) > 1:
        parts = [part.lstrip() if index else part for index, part in enumerate(parts)]
        parts = [part.rstrip() if index < len(parts) - 1 else part for index, part in enumerate(parts)]
    if not all(parts):
        raise ValueError("elipsis '…' harus diapit teks di kedua sisinya")
    return re.compile(f"[^{BARRIER}]*?".join(literal(part) for part in parts))


def fields_balanced(segments: list[Segment], start: int, end: int) -> bool:
    depth = 0
    for segment in segments:
        if segment.kind != "field" or not (start <= segment.start < end):
            continue
        for char in segment.run.iter(w("fldChar")):
            kind = char.get(w("fldCharType"))
            depth += 1 if kind == "begin" else -1 if kind == "end" else 0
            if depth < 0:
                return False
    return depth == 0


def is_listing_paragraph(paragraph: etree._Element) -> bool:
    """Paragraf Daftar Isi/Gambar/Tabel dibuat ulang otomatis oleh Word, jadi tidak disentuh."""
    style = paragraph.find(f"{w('pPr')}/{w('pStyle')}")
    name = (style.get(w("val"), "") if style is not None else "").lower().replace(" ", "")
    return (name.startswith("toc") and name != "tocheading") or name.startswith("tableoffigures")


def find_matches(parts: dict[str, etree._Element], pattern: re.Pattern[str]) -> list[Match]:
    matches: list[Match] = []
    for name, root in parts.items():
        for paragraph in root.iter(w("p")):
            if is_listing_paragraph(paragraph):
                continue
            segments, text = paragraph_model(paragraph)
            normalized = text.translate(NORMALIZE)
            for found in pattern.finditer(normalized):
                if found.end() == found.start():
                    continue
                snippet = text[max(0, found.start() - 30): found.end() + 30].replace(BARRIER, "▯").replace(FIELD, "")
                matches.append(Match(name, paragraph, found.start(), found.end(), snippet))
    return matches


def text_elements(chars: list[str]) -> list[etree._Element]:
    elements: list[etree._Element] = []
    buffer: list[str] = []

    def flush() -> None:
        if buffer:
            element = etree.Element(w("t"))
            element.text = "".join(buffer)
            if element.text != element.text.strip():
                element.set(f"{{{XML_NS}}}space", "preserve")
            elements.append(element)
            buffer.clear()

    for char in chars:
        if char == "\t":
            flush()
            elements.append(etree.Element(w("tab")))
        elif char == "‑":
            flush()
            elements.append(etree.Element(w("noBreakHyphen")))
        else:
            buffer.append(char)
    flush()
    return elements


def make_run(template: etree._Element, chars: list[str], italic: bool = False, fresh: bool = False) -> etree._Element:
    run = etree.Element(w("r"))
    for name, value in template.attrib.items():
        run.set(name, value)
    properties = template.find(w("rPr"))
    if properties is not None or italic:
        properties = deepcopy(properties) if properties is not None else etree.Element(w("rPr"))
        if italic:
            for existing in properties.findall(w("i")):
                properties.remove(existing)
            italic_element = etree.Element(w("i"))
            anchor = None
            for tag in ("rStyle", "rFonts", "b", "bCs"):
                found = properties.find(w(tag))
                if found is not None:
                    anchor = found
            if anchor is None:
                properties.insert(0, italic_element)
            else:
                anchor.addnext(italic_element)
        if fresh:
            for revision_mark in properties.findall(w("rPrChange")):
                properties.remove(revision_mark)
        run.append(properties)
    run.extend(text_elements(chars))
    return run


def isolate(segments: list[Segment], start: int, end: int) -> list[etree._Element]:
    """Pecah run di batas [start, end) dan kembalikan run yang berada tepat di rentang itu."""
    isolated: list[etree._Element] = []
    for segment in segments:
        if segment.end <= start or segment.start >= end:
            continue
        if segment.kind != "text":
            isolated.append(segment.run)
            continue
        cut_from = max(0, start - segment.start)
        cut_to = min(len(segment.chars), end - segment.start)
        if cut_from == 0 and cut_to == len(segment.chars):
            isolated.append(segment.run)
            continue
        pieces = [
            segment.chars[:cut_from],
            segment.chars[cut_from:cut_to],
            segment.chars[cut_to:],
        ]
        new_runs = [make_run(segment.run, piece) if piece else None for piece in pieces]
        for new_run in new_runs:
            if new_run is not None:
                segment.run.addprevious(new_run)
        segment.run.getparent().remove(segment.run)
        isolated.append(new_runs[1])
    return isolated


def parse_styled(ganti: str) -> list[tuple[str, bool]]:
    pieces: list[tuple[str, bool]] = []
    buffer: list[str] = []
    italic = False
    index = 0
    while index < len(ganti):
        char = ganti[index]
        if char == "\\" and ganti[index + 1: index + 2] == "*":
            buffer.append("*")
            index += 2
            continue
        if char == "*":
            if buffer:
                pieces.append(("".join(buffer), italic))
                buffer.clear()
            italic = not italic
        else:
            buffer.append(char)
        index += 1
    if italic:
        raise ValueError("tanda * untuk huruf miring tidak berpasangan")
    if buffer:
        pieces.append(("".join(buffer), italic))
    return pieces


def dominant_template(runs: list[etree._Element]) -> etree._Element:
    text_runs = [run for run in runs if run.tag == w("r") and run_kind(run)[0] == "text"]
    if not text_runs:
        return etree.Element(w("r"))
    return max(text_runs, key=lambda run: len(run_kind(run)[1]))


def revision_attributes(session: Session) -> dict[str, str]:
    attributes = {w("id"): str(session.next_change_id), w("author"): session.author, w("date"): session.date}
    session.next_change_id += 1
    return attributes


def mark_deleted(run: etree._Element, session: Session) -> etree._Element:
    wrapper = etree.Element(w("del"), revision_attributes(session))
    run.addprevious(wrapper)
    wrapper.append(run)
    for element in run.iter(w("t")):
        element.tag = w("delText")
    for element in run.iter(w("instrText")):
        element.tag = w("delInstrText")
    return wrapper


def replace_span(runs: list[etree._Element], ganti: str, session: Session) -> tuple[etree._Element, etree._Element]:
    template = dominant_template(runs)
    new_runs = [make_run(template, list(text), italic, fresh=True) for text, italic in parse_styled(ganti)]

    if not session.tracked:
        if not new_runs:
            new_runs = [etree.Element(w("r"))]
        for new_run in new_runs:
            runs[0].addprevious(new_run)
        for run in runs:
            run.getparent().remove(run)
        return new_runs[0], new_runs[-1]

    wrappers = [mark_deleted(run, session) for run in runs]
    last = wrappers[-1]
    if new_runs:
        inserted = etree.Element(w("ins"), revision_attributes(session))
        inserted.extend(new_runs)
        last.addnext(inserted)
        last = inserted
    return wrappers[0], last


def attach_comment(first: etree._Element, last: etree._Element, text: str, session: Session) -> None:
    comment_id = str(session.next_comment_id)
    session.next_comment_id += 1
    first.addprevious(etree.Element(w("commentRangeStart"), {w("id"): comment_id}))
    end = etree.Element(w("commentRangeEnd"), {w("id"): comment_id})
    last.addnext(end)
    reference = etree.Element(w("r"))
    etree.SubElement(reference, w("commentReference"), {w("id"): comment_id})
    end.addnext(reference)
    session.comments.append((int(comment_id), text))


def apply_match(match: Match, revision: Revision, session: Session, with_comments: bool) -> str | None:
    segments, _ = paragraph_model(match.paragraph)
    if not fields_balanced(segments, match.start, match.end):
        return "rentang memotong sitasi/field; kerjakan manual"
    runs = isolate(segments, match.start, match.end)
    if not runs:
        return "rentang kosong"
    if revision.ganti is not None:
        first, last = replace_span(runs, revision.ganti, session)
    else:
        first, last = runs[0], runs[-1]
    if with_comments and revision.catatan:
        if match.part == "word/document.xml":
            attach_comment(first, last, f"[{revision.id}] {revision.catatan}", session)
        else:
            return "diterapkan; komentar dilewati karena berada di catatan kaki"
    return None


def max_numeric_id(roots: list[etree._Element], tags: tuple[str, ...]) -> int:
    highest = -1
    for root in roots:
        for tag in tags:
            for element in root.iter(w(tag)):
                value = element.get(w("id"), "")
                if value.lstrip("-").isdigit():
                    highest = max(highest, int(value))
    return highest


def build_comments_part(existing: bytes | None, comments: list[tuple[int, str]], session: Session) -> bytes:
    if existing:
        root = etree.fromstring(existing)
    else:
        root = etree.Element(w("comments"), nsmap={"w": W_NS})
    for comment_id, text in comments:
        comment = etree.SubElement(root, w("comment"), {
            w("id"): str(comment_id), w("author"): session.author,
            w("date"): session.date, w("initials"): "".join(word[0] for word in session.author.split()[:3]).upper() or "RN",
        })
        for index, line in enumerate(text.split("\n")):
            paragraph = etree.SubElement(comment, w("p"))
            if index == 0:
                marker = etree.SubElement(paragraph, w("r"))
                etree.SubElement(marker, w("annotationRef"))
            run = etree.SubElement(paragraph, w("r"))
            run.extend(text_elements(list(line)))
    return serialize(root)


def ensure_comments_wiring(files: dict[str, bytes]) -> None:
    rels_name = "word/_rels/document.xml.rels"
    rels = etree.fromstring(files[rels_name])
    if not any(rel.get("Type") == COMMENTS_REL for rel in rels):
        used = {rel.get("Id") for rel in rels}
        number = 1
        while f"rIdKomentar{number}" in used:
            number += 1
        etree.SubElement(rels, f"{{{REL_NS}}}Relationship", {
            "Id": f"rIdKomentar{number}", "Type": COMMENTS_REL, "Target": "comments.xml",
        })
        files[rels_name] = serialize(rels)
    types = etree.fromstring(files["[Content_Types].xml"])
    if not any(item.get("PartName") == "/word/comments.xml" for item in types):
        etree.SubElement(types, f"{{{CT_NS}}}Override", {"PartName": "/word/comments.xml", "ContentType": COMMENTS_CT})
        files["[Content_Types].xml"] = serialize(types)


def comments_part_name(files: dict[str, bytes]) -> str:
    rels = etree.fromstring(files["word/_rels/document.xml.rels"])
    for rel in rels:
        if rel.get("Type") == COMMENTS_REL:
            return "word/" + rel.get("Target").lstrip("/").removeprefix("word/")
    return "word/comments.xml"


def serialize(root: etree._Element) -> bytes:
    return etree.tostring(root, xml_declaration=True, encoding="UTF-8", standalone=True)


def load_revisions(path: Path, only: set[str] | None) -> list[Revision]:
    data = json.loads(path.read_text(encoding="utf-8"))
    items = data["revisi"] if isinstance(data, dict) else data
    revisions: list[Revision] = []
    seen: set[str] = set()
    for number, item in enumerate(items, start=1):
        identifier = str(item.get("id") or f"#{number}")
        if identifier in seen:
            sys.exit(f"id ganda di JSON: {identifier}")
        seen.add(identifier)
        if only and identifier not in only:
            continue
        if not item.get("cari"):
            sys.exit(f"butir {identifier}: 'cari' wajib diisi")
        if item.get("ganti") is None and not item.get("catatan"):
            sys.exit(f"butir {identifier}: isi 'ganti' atau 'catatan'")
        if item.get("ganti") is not None and "\n" in item["ganti"]:
            sys.exit(f"butir {identifier}: 'ganti' tidak boleh berisi baris baru; pecah menjadi butir per paragraf")
        revisions.append(Revision(
            id=identifier, cari=item["cari"], lokasi=item.get("lokasi", ""),
            ganti=item.get("ganti"), catatan=item.get("catatan"), semua=bool(item.get("semua")),
        ))
    return revisions


def run(arguments: argparse.Namespace) -> int:
    source = Path(arguments.naskah)
    output = Path(arguments.output) if arguments.output else source.with_name(f"{source.stem}-revisi{source.suffix}")
    if output.resolve() == source.resolve():
        sys.exit("File keluaran tidak boleh sama dengan file asli.")
    only = set(arguments.hanya.split(",")) if arguments.hanya else None
    revisions = load_revisions(Path(arguments.revisi), only)

    with zipfile.ZipFile(source) as archive:
        infos = archive.infolist()
        files = {info.filename: archive.read(info.filename) for info in infos}
    parts = {name: etree.fromstring(files[name]) for name in TEXT_PARTS if name in files}

    comments_name = comments_part_name(files)
    existing_comments = files.get(comments_name)
    comment_root = etree.fromstring(existing_comments) if existing_comments else None
    session = Session(
        author=arguments.penulis,
        date=datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        tracked=not arguments.langsung,
        next_change_id=max_numeric_id(list(parts.values()), ("ins", "del", "moveFrom", "moveTo", "rPrChange", "pPrChange")) + 1,
        next_comment_id=max(max_numeric_id([comment_root], ("comment",)) if comment_root is not None else -1,
                            max_numeric_id(list(parts.values()), ("commentRangeStart", "commentReference"))) + 1,
    )
    with_comments = not arguments.tanpa_komentar

    for revision in revisions:
        try:
            pattern = compile_pattern(revision.cari)
            if revision.ganti is not None:
                parse_styled(revision.ganti)
        except ValueError as error:
            revision.status, revision.detail = "galat", str(error)
            continue
        if revision.ganti is None and not with_comments:
            revision.status = "dilewati (komentar dimatikan)"
            continue
        matches = find_matches(parts, pattern)
        if not matches:
            revision.status = "tidak ditemukan"
            continue
        if len(matches) > 1 and not revision.semua and not arguments.pertama:
            revision.status = f"ambigu ({len(matches)} tempat)"
            revision.detail = " | ".join(match.snippet for match in matches[:3])
            continue
        targets = matches if revision.semua else matches[:1]
        notes: list[str] = []
        for match in sorted(targets, key=lambda item: item.start, reverse=True):
            note = apply_match(match, revision, session, with_comments)
            if note and not note.startswith("diterapkan"):
                notes.append(note)
                continue
            if note:
                notes.append(note)
            revision.applied += 1
        revision.status = "diterapkan" if revision.applied else "gagal"
        if revision.ganti is None and revision.applied:
            revision.status = "komentar"
        revision.detail = "; ".join(dict.fromkeys(notes))

    report(revisions, arguments.laporan)
    if arguments.uji:
        print("\nMode uji: tidak ada file yang ditulis.")
        return 0

    for name, root in parts.items():
        files[name] = serialize(root)
    if session.comments:
        files[comments_name] = build_comments_part(existing_comments, session.comments, session)
        ensure_comments_wiring(files)

    written = set()
    with zipfile.ZipFile(output, "w", zipfile.ZIP_DEFLATED) as archive:
        for info in infos:
            archive.writestr(info, files[info.filename])
            written.add(info.filename)
        for name, content in files.items():
            if name not in written:
                archive.writestr(name, content)
    with zipfile.ZipFile(output) as archive:
        for name in archive.namelist():
            if name.endswith(".xml") or name.endswith(".rels"):
                etree.fromstring(archive.read(name))
    print(f"\nTersimpan: {output}")
    mode = "Track Changes" if session.tracked else "langsung (tanpa Track Changes)"
    print(f"Mode: {mode}; penulis revisi: {session.author}")
    return 0


def report(revisions: list[Revision], csv_path: str | None) -> None:
    counts: dict[str, int] = {}
    for revision in revisions:
        key = revision.status.split(" (")[0]
        counts[key] = counts.get(key, 0) + 1
        mark = {"diterapkan": "OK ", "komentar": "KMT"}.get(revision.status, "!! ")
        extra = f" x{revision.applied}" if revision.applied > 1 else ""
        print(f"{mark} {revision.id:<10} {revision.status}{extra}" + (f" — {revision.detail}" if revision.detail else ""))
    print("\nRingkasan: " + ", ".join(f"{name} {count}" for name, count in counts.items()))
    if csv_path:
        with open(csv_path, "w", newline="", encoding="utf-8-sig") as handle:
            writer = csv.writer(handle)
            writer.writerow(["id", "lokasi", "status", "jumlah", "detail", "cari"])
            for revision in revisions:
                writer.writerow([revision.id, revision.lokasi, revision.status, revision.applied, revision.detail, revision.cari])
        print(f"Laporan: {csv_path}")


def main() -> int:
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8")
    parser = argparse.ArgumentParser(description="Terapkan revisi naskah ke .docx sebagai Track Changes.")
    parser.add_argument("naskah", help="file naskah .docx")
    parser.add_argument("revisi", help="file revisi .json")
    parser.add_argument("-o", "--output", help="file keluaran (bawaan: <naskah>-revisi.docx)")
    parser.add_argument("--penulis", default="Revisi Naskah", help="nama yang tercatat di Track Changes")
    parser.add_argument("--langsung", action="store_true", help="ganti tanpa Track Changes")
    parser.add_argument("--tanpa-komentar", action="store_true", help="abaikan butir 'catatan'")
    parser.add_argument("--pertama", action="store_true", help="bila ambigu, pakai kemunculan pertama")
    parser.add_argument("--hanya", help="id butir dipisah koma")
    parser.add_argument("--laporan", help="simpan laporan ke CSV")
    parser.add_argument("--uji", action="store_true", help="cek saja tanpa menulis file")
    return run(parser.parse_args())


if __name__ == "__main__":
    sys.exit(main())
