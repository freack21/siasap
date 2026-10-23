#!/usr/bin/env python3
"""Terapkan daftar revisi naskah ke file Word (.docx) sebagai Track Changes.

Setiap butir revisi di file JSON mencari teks di dalam SATU paragraf, lalu:
  - bila ada "ganti": teks lama ditandai terhapus dan teks baru ditandai
    tersisip (Track Changes), sehingga bisa di-Accept/Reject di Word;
  - bila ada "catatan": dipasang komentar Word pada teks tersebut
    (untuk revisi yang harus dikerjakan manual, mis. gambar ulang);
  - bila ada kunci struktural (lihat di bawah): paragraf, baris tabel, bagian
    dokumen lain disisipkan atau dihapus, gambar diganti, nomor daftar diubah.
Format asli (font, ukuran, tebal/miring) dipertahankan.

Pemakaian:
  pip install lxml
  python patch_naskah.py naskah.docx revisi-naskah.json
  python patch_naskah.py naskah.docx revisi-naskah.json -o hasil.docx --penulis "Wan Salma"
  python patch_naskah.py naskah.docx revisi-naskah.json --uji        (cek saja, tanpa menulis)

Opsi lain:
  --langsung        ganti tanpa Track Changes
  --tanpa-komentar  abaikan semua "catatan"
  --tanpa-alasan    komentar hanya untuk butir manual (tanpa "ganti")
  --pertama         bila teks ditemukan di beberapa tempat, pakai yang pertama
  --hanya ID,ID     terapkan butir tertentu saja
  --laporan F.csv   simpan laporan per butir
  --tata-tulis F.json  rapikan tata tulis: miringkan istilah asing, jaga judul
                    tabel/gambar tidak terpisah halaman, hapus spasi ganda
  --nomori-ulang    setelah semua revisi, nomori ulang judul "Tabel B.N" dan
                    "Gambar B.N" per bab sesuai urutan dokumen dan sesuaikan
                    rujukannya di teks (juga lewat "nomori_ulang": true di F.json)

Format JSON:
  {"revisi": [{"id": "2.9-01", "lokasi": "2.9 hlm. 30",
               "cari": "awal kalimat…akhir kalimat", "ganti": "teks baru",
               "catatan": "instruksi manual", "semua": false}]}
  - "…" (elipsis) di "cari" berarti teks apa saja di paragraf yang sama.
  - *teks* di "ganti" ditulis miring; \\* untuk tanda bintang biasa.
  - "semua": true mengganti setiap kemunculan (untuk istilah).
  - "urutan": n memakai kemunculan ke-n (dihitung pada naskah asli) bila
    "cari" ditemukan di beberapa tempat.
  - "utuh": true hanya cocok bila "cari" mencakup seluruh isi paragraf
    (mis. satu sel tabel berisi "Dashboard" saja).
  - "tegak": true teks pengganti tidak ikut miring walaupun teks lama miring.
  - "tetap_komentar": true komentar tetap dipasang walaupun --tanpa-alasan
    (untuk hal yang masih harus dikerjakan atau diputuskan penulis).

Butir struktural (boleh digabung dengan "ganti" pada paragraf yang sama).
Paragraf sasaran ("cari") selalu ditetapkan pada naskah asli, sebelum butir lain
mengubah teks; sisipan berikutnya ke sasaran yang sama diletakkan berurutan.
  - "sisip_paragraf": ["teks", ...] sisipkan paragraf sesudah paragraf "cari"
    ("sebelum": true untuk di depannya) sebagai Track Changes: run di dalam
    w:ins dan w:ins pada rPr tanda paragraf. pPr disalin dari paragraf "cari",
    kecuali "gaya": "<styleId>" (salin pPr paragraf bergaya itu yang terdekat
    sebelum titik sisip, sehingga butir daftar dan judul bernomor otomatis ikut
    nomornya; "Normal" = paragraf tanpa gaya). *miring* didukung. Setiap entri
    boleh berupa objek:
      {"teks": "...", "gaya": "Heading3"}          gaya per paragraf
      {"teks": "...", "seperti": "<cari>"}         salin pPr paragraf itu
      {"tabel": [["sel", ...], ...], "seperti": "<judul atau isi tabel contoh>",
       "lebar": [1, 4, 5]}                         tabel baru (baris tersisip),
                                                   format dari tabel contoh
      {"gambar": "berkas.png"}                     paragraf gambar baru; bentuk
                                                   dan lebar dari gambar terdekat
    Judul "Tabel B.N ..." / "Gambar B.N ..." yang disisipkan dengan gaya judul
    naskah otomatis meniru judul naskah (label tebal, nomor field SEQ).
    Bisa juga dipakai di dalam daftar pustaka Mendeley (field multi-paragraf),
    tetapi entri itu hilang bila Mendeley memperbarui daftar pustaka.
  - "sisip_baris": [["sel1", "sel2", ...], ...] sisipkan baris tabel sesudah
    baris yang memuat "cari"; format baris disalin; w:trPr/w:ins + run di w:ins.
  - "hapus": "paragraf" | "tabel" | "paragraf+tabel" hapus paragraf "cari",
    tabel sesudah judul "cari", atau keduanya (w:del pada tanda paragraf dan
    run; w:trPr/w:del pada baris).
  - "sisip_dokumen": {"file": "lain.docx", "bagian": "<penanda>" atau [...],
    "tanpa_penanda": false, "judul_otomatis": false, "penanda": [...]}
    salin bagian dokumen lain (dari paragraf yang diawali penanda sampai
    penanda berikutnya milik berkas yang sama, atau akhir dokumen) sesudah
    paragraf "cari", sebagai Track Changes. Gaya dipetakan ke gaya naskah (id
    atau nama sama; bila tidak ada, definisinya disalin), daftar bernomor
    disalin dengan id baru, gambar disalin dengan rId dan docPr id baru, judul
    tabel/gambar memakai gaya judul naskah. "judul_otomatis": judul bernomor
    ketikan ("3.7.3 Skenario") ikut penomoran otomatis judul setingkat di
    subbab yang sama bila ada.
  - "ganti_gambar": "berkas.png" ganti gambar di atas judul "cari": lebar tampil
    tetap, tinggi mengikuti rasio baru (maks. 80% tinggi halaman). Word tidak
    bisa melacak penggantian gambar, jadi dicatat dengan komentar "Gambar
    diganti ..." pada judul. Bila berkas belum ada, butir dilewati.
  - "format_nomor": "3.11.%1" ganti teks nomor otomatis daftar yang dipakai
    paragraf "cari" (dan semua paragraf lain di daftar itu) lewat definisi
    daftar baru dan pPrChange, mis. sub-subbab 3.13.x menjadi 3.11.x.
Jalur berkas relatif dicari dari folder naskah, folder JSON, lalu folder kerja.

Penomoran ulang (--nomori-ulang): judul dikenali dari gaya judul naskah (gaya
paragraf "Tabel B.N" tepat di atas tabel / "Gambar B.N" di bawah gambar; bisa
ditetapkan dengan "gaya_judul" di F.json). Nomor berupa teks ketikan maupun
hasil field SEQ diubah sebagai Track Changes; judul yang dihapus tidak
dihitung, judul sisipan dihitung. Rujukan "Tabel 3.5", "Tabel 3.5–3.10",
"Tabel 3.5 sampai 3.10" disesuaikan menurut asal teksnya (naskah atau dokumen
sisipan); nomor ganda diselesaikan dengan judul terdekat. Daftar Isi/Gambar/
Tabel tidak disentuh (perbarui dengan Update Field).

File asli tidak pernah ditimpa.
"""

from __future__ import annotations

import argparse
import csv
import difflib
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
STRUCTURAL_KEYS = ("sisip_paragraf", "sisip_baris", "hapus", "sisip_dokumen", "ganti_gambar", "format_nomor")

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
    urutan: int | None = None
    utuh: bool = False
    tetap_komentar: bool = False
    extra: dict = field(default_factory=dict)
    target: "Match | None" = None

    @property
    def structural(self) -> bool:
        return any(key in self.extra for key in STRUCTURAL_KEYS)

    @property
    def pinned(self) -> bool:
        return self.structural or self.urutan is not None or self.utuh


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
    inserted: dict[int, etree._Element] = field(default_factory=dict)
    origin: dict[int, tuple[str, etree._Element]] = field(default_factory=dict)
    next_drawing_id: int = 1
    base_dirs: list[Path] = field(default_factory=list)

    def remember_inserted(self, element: etree._Element, context: str = "naskah") -> None:
        self.inserted[id(element)] = element
        self.origin[id(element)] = (context, element)

    def is_inserted(self, element: etree._Element) -> bool:
        return id(element) in self.inserted

    def context_of(self, element: etree._Element) -> str:
        node = element
        while node is not None:
            found = self.origin.get(id(node))
            if found is not None and found[1] is node:
                return found[0]
            node = node.getparent()
        return "naskah"

    def resolve_path(self, name: str) -> Path:
        path = Path(name)
        if path.is_absolute():
            return path
        for base in self.base_dirs:
            if (base / path).exists():
                return base / path
        return path


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
        pieces = []
        for token in re.split(r"(\s+)", part):
            if not token:
                continue
            if token.isspace():
                pieces.append(f"[ {FIELD}]* [ {FIELD}]*")
            else:
                pieces.append(f"{FIELD}*".join(re.escape(char) for char in token))
        return "".join(pieces)

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


def find_matches(parts: dict[str, etree._Element], pattern: re.Pattern[str], whole: bool = False,
                 only: etree._Element | None = None) -> list[Match]:
    """Cari pola di setiap paragraf. whole=True: pola harus mencakup seluruh isi paragraf (satu sel tabel)."""
    matches: list[Match] = []
    for name, root in parts.items():
        for paragraph in root.iter(w("p")):
            if is_listing_paragraph(paragraph) or (only is not None and paragraph is not only):
                continue
            segments, text = paragraph_model(paragraph)
            normalized = text.translate(NORMALIZE)
            for found in pattern.finditer(normalized):
                if found.end() == found.start():
                    continue
                if whole and (normalized[:found.start()].strip(f" {FIELD}") or normalized[found.end():].strip(f" {FIELD}")):
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


def replace_span(runs: list[etree._Element], pieces: list[tuple[str, bool]], session: Session,
                 upright: bool = False) -> tuple[etree._Element, etree._Element]:
    template = dominant_template(runs)
    if upright:
        template = plain_template(template)
    new_runs = [make_run(template, list(text), italic, fresh=True) for text, italic in pieces]

    if not session.tracked:
        if not new_runs:
            new_runs = [etree.Element(w("r"))]
        for new_run in new_runs:
            runs[0].addprevious(new_run)
        for run in runs:
            run.getparent().remove(run)
        return new_runs[0], new_runs[-1]

    if all(inside_insertion(run) for run in runs):
        # Teks hasil sisipan sendiri diubah di tempat; w:ins tidak boleh bersarang.
        if not new_runs:
            new_runs = [etree.Element(w("r"))]
        for new_run in reversed(new_runs):
            runs[-1].addnext(new_run)
        for run in runs:
            run.getparent().remove(run)
        return new_runs[0], new_runs[-1]
    first = last = None
    for run in runs:
        if inside_insertion(run):
            run.getparent().remove(run)
            continue
        wrapper = mark_deleted(run, session)
        first = first if first is not None else wrapper
        last = wrapper
    if new_runs:
        inserted = etree.Element(w("ins"), revision_attributes(session))
        inserted.extend(new_runs)
        last.addnext(inserted)
        last = inserted
    return first, last


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


ALREADY_DONE = "sudah sesuai"


def inside_word(text: str, index: int) -> bool:
    return 0 < index < len(text) and not text[index - 1].isspace() and not text[index].isspace()


def shared_affixes(old: str, new: str) -> tuple[int, int]:
    """Panjang awalan dan akhiran yang sama, dipotong di batas kata agar coretan rapi."""
    limit = min(len(old), len(new))
    prefix = 0
    while prefix < limit and old[prefix] == new[prefix]:
        prefix += 1
    while prefix > 0 and (inside_word(old, prefix) or inside_word(new, prefix)):
        prefix -= 1
    limit = min(len(old), len(new)) - prefix
    suffix = 0
    while suffix < limit and old[-1 - suffix] == new[-1 - suffix]:
        suffix += 1
    while suffix > 0 and (inside_word(old, len(old) - suffix) or inside_word(new, len(new) - suffix)):
        suffix -= 1
    return prefix, suffix


def word_edits(old: str, new: str) -> list[tuple[int, int, int, int]]:
    """Daftar (dari, sampai) teks lama dan baru yang berubah, per kata.

    Bila kalimat berubah total (kemiripan < 50%), seluruh bagian yang berbeda
    diganti sebagai satu blok agar coretan tetap mudah dibaca.
    """
    old_tokens = re.findall(r"\s+|\S+", old)
    new_tokens = re.findall(r"\s+|\S+", new)
    matcher = difflib.SequenceMatcher(None, old_tokens, new_tokens, autojunk=False)
    if matcher.ratio() < 0.5:
        prefix, suffix = shared_affixes(old, new)
        return [(prefix, len(old) - suffix, prefix, len(new) - suffix)]

    def offsets(tokens: list[str]) -> list[int]:
        result = [0]
        for token in tokens:
            result.append(result[-1] + len(token))
        return result

    old_offsets, new_offsets = offsets(old_tokens), offsets(new_tokens)
    edits = []
    for tag, old_from, old_to, new_from, new_to in matcher.get_opcodes():
        if tag != "equal":
            edits.append((old_offsets[old_from], old_offsets[old_to], new_offsets[new_from], new_offsets[new_to]))
    return edits


def slice_pieces(pieces: list[tuple[str, bool]], start: int, end: int) -> list[tuple[str, bool]]:
    sliced: list[tuple[str, bool]] = []
    offset = 0
    for text, italic in pieces:
        piece_start, piece_end = max(start, offset), min(end, offset + len(text))
        if piece_start < piece_end:
            sliced.append((text[piece_start - offset: piece_end - offset], italic))
        offset += len(text)
    return sliced


def insert_at(segments: list[Segment], position: int, pieces: list[tuple[str, bool]], session: Session) -> tuple[etree._Element, etree._Element] | None:
    """Sisipkan teks baru di satu titik paragraf (tanpa menghapus apa pun)."""
    anchor, after = None, True
    for segment in segments:
        if segment.kind == "text" and segment.start < position <= segment.end:
            anchor = isolate([segment], segment.start, position)[0] if position < segment.end else segment.run
            break
    if anchor is None:
        for segment in segments:
            if segment.kind == "text" and segment.start >= position:
                anchor, after = segment.run, False
                break
    if anchor is None:
        return None
    new_runs = [make_run(anchor, list(text), italic, fresh=True) for text, italic in pieces]
    tracked_here = session.tracked and not inside_insertion(anchor)
    holder = etree.Element(w("ins"), revision_attributes(session)) if tracked_here else None
    if holder is not None:
        holder.extend(new_runs)
        placed = [holder]
    else:
        placed = new_runs
    for element in (placed if not after else reversed(placed)):
        if after:
            anchor.addnext(element)
        else:
            anchor.addprevious(element)
    return placed[0], placed[-1]


def apply_match(match: Match, revision: Revision, session: Session, with_comments: bool) -> str | None:
    segments, text = paragraph_model(match.paragraph)
    if revision.ganti is None:
        if not fields_balanced(segments, match.start, match.end):
            return "rentang memotong sitasi/field; kerjakan manual"
        runs = isolate(segments, match.start, match.end)
        first, last = runs[0], runs[-1]
    else:
        pieces = parse_styled(revision.ganti)
        new_plain = "".join(piece for piece, _ in pieces)
        positions = [index for index in range(match.start, match.end) if text[index] != FIELD]
        old_plain = "".join(text[index] for index in positions)
        old_key, new_key = old_plain.translate(NORMALIZE), new_plain.translate(NORMALIZE)
        if old_key == new_key:
            return ALREADY_DONE
        edits = word_edits(old_key, new_key)

        def raw_range(old_from: int, old_to: int) -> tuple[int, int]:
            start = positions[old_from] if old_from < len(positions) else match.end
            end = positions[old_to - 1] + 1 if old_to > old_from else start
            return start, end

        for old_from, old_to, _, _ in edits:
            start, end = raw_range(old_from, old_to)
            if not fields_balanced(segments, start, end):
                return "rentang memotong sitasi/field; kerjakan manual"
        placed_ranges = []
        for old_from, old_to, new_from, new_to in reversed(edits):
            segments, _ = paragraph_model(match.paragraph)
            start, end = raw_range(old_from, old_to)
            inserted = slice_pieces(pieces, new_from, new_to)
            if start == end:
                placed = insert_at(segments, start, inserted, session)
                if placed is None:
                    return "titik sisip tidak ditemukan"
            else:
                placed = replace_span(isolate(segments, start, end), inserted, session, upright=revision.extra.get("tegak", False))
            placed_ranges.append(placed)
        first, last = placed_ranges[-1][0], placed_ranges[0][1]
    if with_comments and revision.catatan:
        if match.part == "word/document.xml":
            attach_comment(first, last, f"[{revision.id}] {revision.catatan}", session)
        else:
            return "diterapkan; komentar dilewati karena berada di catatan kaki"
    return None


PARAGRAPH_PROPERTY_ORDER = [
    "pStyle", "keepNext", "keepLines", "pageBreakBefore", "framePr", "widowControl", "numPr",
    "suppressLineNumbers", "pBdr", "shd", "tabs", "suppressAutoHyphens", "kinsoku", "wordWrap",
    "overflowPunct", "topLinePunct", "autoSpaceDE", "autoSpaceDN", "bidi", "adjustRightInd",
    "snapToGrid", "spacing", "ind", "contextualSpacing", "mirrorIndents", "suppressOverlap", "jc",
    "textDirection", "textAlignment", "textboxTightWrap", "outlineLvl", "divId", "cnfStyle",
    "rPr", "sectPr", "pPrChange",
]
RUN_PROPERTY_ORDER = [
    "rStyle", "rFonts", "b", "bCs", "i", "iCs", "caps", "smallCaps", "strike", "dstrike", "outline",
    "shadow", "emboss", "imprint", "noProof", "snapToGrid", "vanish", "webHidden", "color", "spacing",
    "w", "kern", "position", "sz", "szCs", "highlight", "u", "effect", "bdr", "shd", "fitText",
    "vertAlign", "rtl", "cs", "em", "lang", "eastAsianLayout", "specVanish", "oMath", "rPrChange",
]
SMALL_WORDS = {"of", "in", "and", "the", "for", "with", "on", "a", "an", "to", "using", "based", "at", "by", "&"}


def place_in_order(parent: etree._Element, element: etree._Element, order: list[str]) -> None:
    rank = {w(name): index for index, name in enumerate(order)}
    target = rank[element.tag]
    for index, child in enumerate(parent):
        if rank.get(child.tag, -1) > target:
            parent.insert(index, element)
            return
    parent.append(element)


def is_on(element: etree._Element | None) -> bool | None:
    if element is None:
        return None
    return element.get(w("val"), "true") not in ("0", "false", "off")


class StyleBook:
    """Sifat gaya (miring, keep with next) termasuk yang diwarisi lewat basedOn."""

    def __init__(self, root: etree._Element | None) -> None:
        self.styles: dict[str, etree._Element] = {}
        self.default_paragraph: str | None = None
        if root is None:
            return
        for style in root.iter(w("style")):
            identifier = style.get(w("styleId"))
            self.styles[identifier] = style
            if style.get(w("type")) == "paragraph" and style.get(w("default")) in ("1", "true"):
                self.default_paragraph = identifier

    def lookup(self, style_id: str | None, path: str) -> bool:
        seen = 0
        while style_id and style_id in self.styles and seen < 15:
            style = self.styles[style_id]
            value = is_on(style.find(path))
            if value is not None:
                return value
            based = style.find(w("basedOn"))
            style_id = based.get(w("val")) if based is not None else None
            seen += 1
        return False

    def paragraph_style(self, paragraph: etree._Element) -> str | None:
        style = paragraph.find(f"{w('pPr')}/{w('pStyle')}")
        return style.get(w("val")) if style is not None else self.default_paragraph

    def keeps_with_next(self, paragraph: etree._Element) -> bool:
        direct = is_on(paragraph.find(f"{w('pPr')}/{w('keepNext')}"))
        if direct is not None:
            return direct
        return self.lookup(self.paragraph_style(paragraph), f"{w('pPr')}/{w('keepNext')}")

    def is_italic(self, run: etree._Element, paragraph: etree._Element) -> bool:
        direct = is_on(run.find(f"{w('rPr')}/{w('i')}"))
        if direct is not None:
            return direct
        character_style = run.find(f"{w('rPr')}/{w('rStyle')}")
        if character_style is not None and self.lookup(character_style.get(w("val")), f"{w('rPr')}/{w('i')}"):
            return True
        return self.lookup(self.paragraph_style(paragraph), f"{w('rPr')}/{w('i')}")


def inside_insertion(element: etree._Element) -> bool:
    return any(ancestor.tag == w("ins") for ancestor in element.iterancestors())


def set_keep_next(paragraph: etree._Element, session: Session) -> None:
    properties = paragraph.find(w("pPr"))
    if properties is None:
        properties = etree.Element(w("pPr"))
        paragraph.insert(0, properties)
    change = None
    if session.tracked and properties.find(w("pPrChange")) is None and properties.find(f"{w('rPr')}/{w('ins')}") is None:
        previous = etree.Element(w("pPr"))
        for child in properties:
            if child.tag not in (w("rPr"), w("sectPr"), w("pPrChange")):
                previous.append(deepcopy(child))
        change = etree.Element(w("pPrChange"), revision_attributes(session))
        change.append(previous)
    for existing in properties.findall(w("keepNext")):
        properties.remove(existing)
    place_in_order(properties, etree.Element(w("keepNext")), PARAGRAPH_PROPERTY_ORDER)
    if change is not None:
        place_in_order(properties, change, PARAGRAPH_PROPERTY_ORDER)


def set_italic(run: etree._Element, session: Session) -> None:
    properties = run.find(w("rPr"))
    if properties is None:
        properties = etree.Element(w("rPr"))
        run.insert(0, properties)
    change = None
    if session.tracked and not inside_insertion(run) and properties.find(w("rPrChange")) is None:
        previous = etree.Element(w("rPr"))
        for child in properties:
            if child.tag != w("rPrChange"):
                previous.append(deepcopy(child))
        change = etree.Element(w("rPrChange"), revision_attributes(session))
        change.append(previous)
    for existing in properties.findall(w("i")):
        properties.remove(existing)
    place_in_order(properties, etree.Element(w("i")), RUN_PROPERTY_ORDER)
    if change is not None:
        place_in_order(properties, change, RUN_PROPERTY_ORDER)


def delete_runs(runs: list[etree._Element], session: Session) -> None:
    for run in runs:
        if session.tracked and not inside_insertion(run):
            mark_deleted(run, session)
        else:
            run.getparent().remove(run)


def paragraph_plain_text(paragraph: etree._Element) -> str:
    return "".join(element.text or "" for element in paragraph.iter(w("t")))


def excluded_paragraphs(body: etree._Element, config: dict) -> set[etree._Element]:
    """Paragraf yang tidak dimiringkan: daftar pustaka dan tabel tertentu (mis. Tabel 2.1).

    Elemennya sendiri yang disimpan (bukan id()), karena proxy lxml yang tidak dipegang
    bisa dibuang lalu id-nya dipakai ulang oleh paragraf lain.
    """
    excluded: set[etree._Element] = set()
    after_heading = config.get("kecualikan_setelah_judul", "DAFTAR PUSTAKA").upper()
    tables = [str(number) for number in config.get("kecualikan_tabel", [])]
    children = list(body)
    bibliography = False
    for index, child in enumerate(children):
        if child.tag == w("p"):
            text = paragraph_plain_text(child).strip().upper()
            if text == after_heading and not is_listing_paragraph(child):
                bibliography = True
        if bibliography:
            excluded.update(child.iter(w("p")))
        elif child.tag == w("tbl") and index > 0 and children[index - 1].tag == w("p"):
            caption = paragraph_plain_text(children[index - 1]).strip()
            if any(re.match(rf"Tabel {re.escape(number)}\b", caption) for number in tables):
                excluded.update(child.iter(w("p")))
    return excluded


def in_english_title(text: str, start: int, end: int) -> bool:
    """True bila istilah berada di judul berbahasa Inggris (Title Case) atau teks kapital semua."""
    matched = text[start:end]
    if matched.isupper() and len(matched) > 3:
        return True
    tokens = list(re.finditer(r"[^\s()\[\]\"',:;]+", text))
    position = next((index for index, token in enumerate(tokens) if token.start() <= start < token.end()), None)
    if position is None:
        return False

    def title_word(token: str) -> bool:
        return token[:1].isupper() or token.lower() in SMALL_WORDS

    left = position
    while left > 0 and title_word(tokens[left - 1].group()):
        left -= 1
    right = position
    while right + 1 < len(tokens) and title_word(tokens[right + 1].group()):
        right += 1
    capitalised = sum(1 for token in tokens[left:right + 1] if token.group()[:1].isupper())
    return capitalised >= 4


def italicise_terms(body: etree._Element, config: dict, styles: StyleBook, session: Session) -> int:
    terms = sorted(config.get("istilah_asing", []), key=len, reverse=True)
    if not terms:
        return 0
    pattern = re.compile(r"(?<![\w-])(" + "|".join(re.escape(term) for term in terms) + r")(?![\w-])", re.IGNORECASE)
    excluded = excluded_paragraphs(body, config)
    changed = 0
    for paragraph in list(body.iter(w("p"))):
        if paragraph in excluded or is_listing_paragraph(paragraph):
            continue
        segments, text = paragraph_model(paragraph)
        flags = []
        for segment in segments:
            italic = segment.kind == "text" and styles.is_italic(segment.run, paragraph)
            flags.extend([italic] * len(segment.chars))
        targets = [match for match in pattern.finditer(text)
                   if not all(flags[match.start():match.end()]) and not in_english_title(text, match.start(), match.end())]
        for match in reversed(targets):
            segments, _ = paragraph_model(paragraph)
            for run in isolate(segments, match.start(), match.end()):
                if run.tag == w("r") and run_kind(run)[0] == "text" and not styles.is_italic(run, paragraph):
                    set_italic(run, session)
            changed += 1
    return changed


def keep_captions_with_objects(body: etree._Element, styles: StyleBook, session: Session) -> int:
    children = list(body)
    changed = 0
    for index, child in enumerate(children):
        if child.tag != w("p"):
            continue
        text = paragraph_plain_text(child).strip()
        if re.match(r"Tabel \d+\.\d+", text) and index + 1 < len(children) and children[index + 1].tag == w("tbl"):
            if not styles.keeps_with_next(child):
                set_keep_next(child, session)
                changed += 1
        if re.match(r"Gambar \d+\.\d+", text) and not is_listing_paragraph(child):
            previous = index - 1
            while previous >= 0 and children[previous].tag == w("p") and not paragraph_plain_text(children[previous]).strip() \
                    and children[previous].find(f".//{w('drawing')}") is None and children[previous].find(f".//{w('pict')}") is None:
                previous -= 1
            if previous >= 0 and children[previous].tag == w("p") and (
                    children[previous].find(f".//{w('drawing')}") is not None or children[previous].find(f".//{w('pict')}") is not None):
                for figure_index in range(previous, index):
                    if not styles.keeps_with_next(children[figure_index]):
                        set_keep_next(children[figure_index], session)
                        changed += 1
    return changed


SPACE_RULES = [
    re.compile(r"(?<=[^ \x00\x01\t]) (?P<hapus> +)(?=[^ \x00\x01\t])"),
    re.compile(r"(?<=[\w\)\]\"'])(?P<hapus> +)(?=[,.;)](?:[ \t]|$|[,.;]))"),
    re.compile(r"(?<=\()(?P<hapus> +)(?=\S)"),
]


def tidy_spaces(body: etree._Element, session: Session) -> int:
    changed = 0
    for paragraph in list(body.iter(w("p"))):
        if is_listing_paragraph(paragraph):
            continue
        _, text = paragraph_model(paragraph)
        if "{" in text or "\t" in text.strip():
            continue
        spans = set()
        for rule in SPACE_RULES:
            for match in rule.finditer(text):
                spans.add((match.start("hapus"), match.end("hapus")))
        for start, end in sorted(spans, reverse=True):
            segments, current = paragraph_model(paragraph)
            if current[start:end].strip(" "):
                continue
            delete_runs(isolate(segments, start, end), session)
            changed += 1
    return changed


def apply_layout_rules(body: etree._Element, config: dict, styles: StyleBook, session: Session) -> dict[str, int]:
    counts = {}
    if config.get("jaga_judul", True):
        counts["judul tabel/gambar dijaga"] = keep_captions_with_objects(body, styles, session)
    counts["istilah asing dimiringkan"] = italicise_terms(body, config, styles, session)
    if config.get("rapikan_spasi", True):
        counts["spasi dirapikan"] = tidy_spaces(body, session)
    return counts


# ---------------------------------------------------------------------------
# Paket .docx: bagian XML lain (numbering, styles, relasi, media) yang diubah.
# ---------------------------------------------------------------------------

R_NS = "http://schemas.openxmlformats.org/officeDocument/2006/relationships"
WP_NS = "http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
A_NS = "http://schemas.openxmlformats.org/drawingml/2006/main"
PIC_NS = "http://schemas.openxmlformats.org/drawingml/2006/picture"
W14_NS = "http://schemas.microsoft.com/office/word/2010/wordml"
IMAGE_REL = "http://schemas.openxmlformats.org/officeDocument/2006/relationships/image"
HYPERLINK_REL = "http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink"
NUMBERING_REL = "http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering"
NUMBERING_CT = "application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"
IMAGE_TYPES = {"png": "image/png", "jpg": "image/jpeg", "jpeg": "image/jpeg", "gif": "image/gif", "bmp": "image/bmp"}
EMU_PER_TWIP = 635


class Package:
    """Isi zip .docx beserta bagian XML yang sudah di-parse dan perlu ditulis ulang."""

    def __init__(self, files: dict[str, bytes]) -> None:
        self.files = files
        self.roots: dict[str, etree._Element] = {}

    def root(self, name: str) -> etree._Element:
        if name not in self.roots:
            self.roots[name] = etree.fromstring(self.files[name])
        return self.roots[name]

    def numbering(self) -> etree._Element:
        name = "word/numbering.xml"
        if name not in self.files:
            self.files[name] = serialize(etree.Element(w("numbering"), nsmap={"w": W_NS}))
            self.add_relationship(NUMBERING_REL, "numbering.xml")
            self.override_content_type("/word/numbering.xml", NUMBERING_CT)
        return self.root(name)

    def styles(self) -> etree._Element:
        return self.root("word/styles.xml")

    def relationships(self) -> etree._Element:
        return self.root("word/_rels/document.xml.rels")

    def add_relationship(self, rel_type: str, target: str, external: bool = False) -> str:
        rels = self.relationships()
        used = {rel.get("Id") for rel in rels}
        number = len(used) + 1
        while f"rId{number}" in used:
            number += 1
        attributes = {"Id": f"rId{number}", "Type": rel_type, "Target": target}
        if external:
            attributes["TargetMode"] = "External"
        etree.SubElement(rels, f"{{{REL_NS}}}Relationship", attributes)
        return f"rId{number}"

    def relationship_target(self, rel_id: str) -> str | None:
        for rel in self.relationships():
            if rel.get("Id") == rel_id:
                return rel.get("Target")
        return None

    def override_content_type(self, part: str, content_type: str) -> None:
        types = self.root("[Content_Types].xml")
        if not any(item.get("PartName") == part for item in types):
            etree.SubElement(types, f"{{{CT_NS}}}Override", {"PartName": part, "ContentType": content_type})

    def default_content_type(self, extension: str, content_type: str) -> None:
        types = self.root("[Content_Types].xml")
        if not any((item.get("Extension") or "").lower() == extension for item in types):
            etree.SubElement(types, f"{{{CT_NS}}}Default", {"Extension": extension, "ContentType": content_type})

    def add_media(self, data: bytes, extension: str, stem: str = "gambar") -> str:
        """Simpan berkas gambar baru di word/media dan kembalikan rId relasinya."""
        extension = extension.lower().lstrip(".")
        stem = re.sub(r"[^A-Za-z0-9_-]+", "-", stem).strip("-") or "gambar"
        number = 1
        while f"word/media/{stem}-{number}.{extension}" in self.files:
            number += 1
        name = f"media/{stem}-{number}.{extension}"
        self.files[f"word/{name}"] = data
        self.default_content_type(extension, IMAGE_TYPES.get(extension, f"image/{extension}"))
        return self.add_relationship(IMAGE_REL, name)

    def flush(self) -> None:
        for name, root in self.roots.items():
            self.files[name] = serialize(root)


# ---------------------------------------------------------------------------
# Paragraf dan baris tabel sebagai Track Changes (sisip/hapus).
# ---------------------------------------------------------------------------

def ensure_child(parent: etree._Element, tag: str, order: list[str] | None = None, first: bool = False) -> etree._Element:
    found = parent.find(w(tag))
    if found is None:
        found = etree.Element(w(tag))
        if first:
            parent.insert(0, found)
        elif order:
            place_in_order(parent, found, order)
        else:
            parent.append(found)
    return found


def paragraph_mark_properties(paragraph: etree._Element) -> etree._Element:
    properties = paragraph.find(w("pPr"))
    if properties is None:
        properties = etree.Element(w("pPr"))
        paragraph.insert(0, properties)
    return ensure_child(properties, "rPr", PARAGRAPH_PROPERTY_ORDER)


def mark_paragraph(paragraph: etree._Element, tag: str, session: Session) -> None:
    """Tandai tanda paragraf sebagai disisipkan (ins) atau dihapus (del); harus anak pertama rPr."""
    mark = paragraph_mark_properties(paragraph)
    for existing in mark.findall(w(tag)):
        mark.remove(existing)
    mark.insert(0, etree.Element(w(tag), revision_attributes(session)))


def paragraph_inserted(paragraph: etree._Element) -> bool:
    return paragraph.find(f"{w('pPr')}/{w('rPr')}/{w('ins')}") is not None


def paragraph_deleted(paragraph: etree._Element) -> bool:
    return paragraph.find(f"{w('pPr')}/{w('rPr')}/{w('del')}") is not None


def wrap_runs(container: etree._Element, tag: str, session: Session) -> None:
    """Bungkus setiap kelompok run berurutan dalam w:ins/w:del (masuk ke hyperlink, smartTag, dll.)."""
    group: list[etree._Element] = []

    def flush() -> None:
        if group:
            wrapper = etree.Element(w(tag), revision_attributes(session))
            group[0].addprevious(wrapper)
            wrapper.extend(group)
            group.clear()

    for child in list(container):
        if child.tag == w("r"):
            group.append(child)
            continue
        flush()
        if child.tag in (w("hyperlink"), w("smartTag"), w("customXml"), w("fldSimple")):
            wrap_runs(child, tag, session)
        elif child.tag in (w("sdt"),):
            content = child.find(w("sdtContent"))
            if content is not None:
                wrap_runs(content, tag, session)
    flush()


def mark_paragraph_inserted(paragraph: etree._Element, session: Session) -> None:
    if not session.tracked:
        return
    wrap_runs(paragraph, "ins", session)
    mark_paragraph(paragraph, "ins", session)


def clean_properties(properties: etree._Element | None, tag: str = "pPr") -> etree._Element:
    """Salinan pPr tanpa jejak revisi, sectPr, atau tanda paragraf lama."""
    result = deepcopy(properties) if properties is not None else etree.Element(w(tag))
    for unwanted in ("sectPr", "pPrChange", "rPrChange", "trPrChange", "tcPrChange", "ins", "del"):
        for element in result.findall(w(unwanted)):
            result.remove(element)
    mark = result.find(w("rPr"))
    if mark is not None:
        for unwanted in ("ins", "del", "rPrChange", "moveFrom", "moveTo"):
            for element in mark.findall(w(unwanted)):
                mark.remove(element)
    return result


ITALIC_CHARACTER_STYLES: set[str] = set()


def plain_template(run: etree._Element) -> etree._Element:
    """Run contoh tanpa huruf miring (miring hanya lewat *...*), termasuk gaya karakter miring seperti Emphasis."""
    template = etree.Element(w("r"))
    properties = run.find(w("rPr"))
    if properties is not None:
        properties = deepcopy(properties)
        for tag in ("i", "iCs", "rPrChange"):
            for element in properties.findall(w(tag)):
                properties.remove(element)
        for style in properties.findall(w("rStyle")):
            if style.get(w("val")) in ITALIC_CHARACTER_STYLES:
                properties.remove(style)
        template.append(properties)
    return template


def paragraph_template_run(paragraph: etree._Element) -> etree._Element:
    return plain_template(dominant_template(list(iter_runs(paragraph))))


def build_paragraph(source: etree._Element | None, text: str, session: Session,
                    template: etree._Element | None = None, style: str | None = None) -> etree._Element:
    """Paragraf baru tersisip (Track Changes) dengan pPr disalin dari paragraf contoh."""
    paragraph = etree.Element(w("p"))
    properties = clean_properties(source.find(w("pPr")) if source is not None else None)
    if style:
        style_element = properties.find(w("pStyle"))
        if style_element is None:
            style_element = etree.Element(w("pStyle"))
            properties.insert(0, style_element)
        style_element.set(w("val"), style)
    paragraph.append(properties)
    if template is None:
        template = paragraph_template_run(source) if source is not None else etree.Element(w("r"))
    for piece, italic in parse_styled(text):
        paragraph.append(make_run(template, list(piece), italic, fresh=True))
    if not len(properties):
        paragraph.remove(properties)
    mark_paragraph_inserted(paragraph, session)
    return paragraph


def delete_paragraph(paragraph: etree._Element, session: Session, mark: bool = True) -> None:
    """Hapus seluruh isi paragraf (dan tanda paragrafnya) sebagai Track Changes."""
    for run in list(iter_runs(paragraph)):
        if run.tag != w("r"):
            continue
        if not session.tracked or inside_insertion(run):
            run.getparent().remove(run)
        else:
            mark_deleted(run, session)
    if mark and session.tracked:
        mark_paragraph(paragraph, "del", session)


def row_properties(row: etree._Element) -> etree._Element:
    properties = row.find(w("trPr"))
    if properties is None:
        properties = etree.Element(w("trPr"))
        anchor = row.find(w("tblPrEx"))
        if anchor is not None:
            anchor.addnext(properties)
        else:
            row.insert(0, properties)
    return properties


def mark_row(row: etree._Element, tag: str, session: Session) -> None:
    properties = row_properties(row)
    for existing in properties.findall(w(tag)):
        properties.remove(existing)
    marker = etree.Element(w(tag), revision_attributes(session))
    change = properties.find(w("trPrChange"))
    if change is not None:
        change.addprevious(marker)
    else:
        properties.append(marker)


def mark_row_inserted(row: etree._Element, session: Session) -> None:
    if not session.tracked:
        return
    mark_row(row, "ins", session)
    for paragraph in row.iter(w("p")):
        mark_paragraph_inserted(paragraph, session)


def mark_table_inserted(table: etree._Element, session: Session) -> None:
    for row in table.findall(w("tr")):
        mark_row_inserted(row, session)


def delete_table(table: etree._Element, session: Session) -> None:
    if not session.tracked:
        table.getparent().remove(table)
        return
    for row in table.findall(w("tr")):
        mark_row(row, "del", session)
        for paragraph in row.iter(w("p")):
            delete_paragraph(paragraph, session)


def fill_cell(cell: etree._Element, text: str, session: Session, template: etree._Element | None = None) -> None:
    """Kosongkan sel lalu isi dengan teks baru; pPr dan format run diambil dari isi sel lama."""
    paragraphs = cell.findall(w("p"))
    source = paragraphs[0] if paragraphs else None
    if template is None:
        runs = [run for paragraph in paragraphs for run in iter_runs(paragraph)]
        template = plain_template(dominant_template(runs))
    for child in list(cell):
        if child.tag != w("tcPr"):
            cell.remove(child)
    lines = text.split("\n") if text else [""]
    for line in lines:
        paragraph = etree.SubElement(cell, w("p"))
        properties = clean_properties(source.find(w("pPr")) if source is not None else None)
        if len(properties):
            paragraph.append(properties)
        for piece, italic in parse_styled(line):
            paragraph.append(make_run(template, list(piece), italic, fresh=True))


def clone_row(row: etree._Element, values: list[str], session: Session) -> etree._Element:
    cells = row.findall(w("tc"))
    if len(values) > len(cells):
        raise ValueError(f"baris berisi {len(values)} sel, tabel hanya {len(cells)} kolom")
    templates = []
    for cell in cells:
        runs = [run for paragraph in cell.findall(w("p")) for run in iter_runs(paragraph)]
        templates.append(plain_template(dominant_template(runs)))
    clone = deepcopy(row)
    for element in list(clone.iter(w("bookmarkStart"), w("bookmarkEnd"), w("commentRangeStart"), w("commentRangeEnd"))):
        element.getparent().remove(element)
    properties = clone.find(w("trPr"))
    if properties is not None:
        for unwanted in ("ins", "del", "trPrChange"):
            for element in properties.findall(w(unwanted)):
                properties.remove(element)
    for index, cell in enumerate(clone.findall(w("tc"))):
        cell_properties = cell.find(w("tcPr"))
        if cell_properties is not None:
            for unwanted in ("vMerge", "tcPrChange", "cellIns", "cellDel"):
                for element in cell_properties.findall(w(unwanted)):
                    cell_properties.remove(element)
        fill_cell(cell, values[index] if index < len(values) else "", session, templates[index])
    mark_row_inserted(clone, session)
    return clone


def set_column_widths(table: etree._Element, weights: list[float]) -> None:
    """Bagi lebar total tabel ke kolom-kolom menurut bobot relatif."""
    grid = table.find(w("tblGrid"))
    columns = grid.findall(w("gridCol")) if grid is not None else []
    if len(columns) != len(weights):
        raise ValueError(f"'lebar' berisi {len(weights)} angka, tabel punya {len(columns)} kolom")
    total = sum(int(column.get(w("w"), "0")) for column in columns)
    widths = [round(total * weight / sum(weights)) for weight in weights]
    for column, width in zip(columns, widths):
        column.set(w("w"), str(width))
    for row in table.findall(w("tr")):
        for cell, width in zip(row.findall(w("tc")), widths):
            cell_width = cell.find(f"{w('tcPr')}/{w('tcW')}")
            if cell_width is not None:
                cell_width.set(w("w"), str(width))
                cell_width.set(w("type"), "dxa")


def build_table(template: etree._Element, rows: list[list[str]], session: Session) -> etree._Element:
    """Tabel baru dengan format (tblPr, lebar kolom, baris judul dan isi) disalin dari tabel contoh."""
    template_rows = template.findall(w("tr"))
    if not template_rows:
        raise ValueError("tabel contoh kosong")
    columns = len(template_rows[0].findall(w("tc")))
    if any(len(row) != columns for row in rows):
        raise ValueError(f"tabel contoh punya {columns} kolom; samakan jumlah sel setiap baris")
    table = etree.Element(w("tbl"))
    for child in template:
        if child.tag in (w("tblPr"), w("tblGrid")):
            table.append(clean_properties(child, "tblPr"))
    for index, values in enumerate(rows):
        source = template_rows[0] if index == 0 else template_rows[min(1, len(template_rows) - 1)]
        clone = clone_row(source, values, session)
        if index > 0:
            properties = clone.find(w("trPr"))
            if properties is not None:
                for element in properties.findall(w("tblHeader")):
                    properties.remove(element)
        table.append(clone)
    return table


def insert_elements(anchor: etree._Element, elements: list[etree._Element], session: Session,
                    before: bool = False, context: str = "naskah") -> None:
    """Sisipkan elemen di sebelah jangkar; sisipan berikutnya ke jangkar yang sama tetap berurutan."""
    if before:
        for element in elements:
            anchor.addprevious(element)
    else:
        last = anchor
        while last.getnext() is not None and session.is_inserted(last.getnext()):
            last = last.getnext()
        for element in elements:
            last.addnext(element)
            last = element
    for element in elements:
        session.remember_inserted(element, context)


def attach_comment_to_block(elements: list[etree._Element], text: str, session: Session) -> bool:
    """Komentar yang mencakup sekumpulan paragraf/tabel hasil sisipan."""
    paragraphs = [paragraph for element in elements
                  for paragraph in ([element] if element.tag == w("p") else element.iter(w("p")))]
    filled = [paragraph for paragraph in paragraphs if [child for child in paragraph if child.tag != w("pPr")]]
    if not filled:
        return False
    first = [child for child in filled[0] if child.tag != w("pPr")][0]
    last = [child for child in filled[-1] if child.tag != w("pPr")][-1]
    attach_comment(first, last, text, session)
    return True

# ---------------------------------------------------------------------------
# Judul tabel/gambar ("Tabel 3.5 ...", "Gambar 3.2 ...").
# ---------------------------------------------------------------------------

CAPTION_RE = re.compile(r"^\s*(Tabel|Gambar)\s+(\d+)\.(\d+)")
REFERENCE_RE = re.compile(
    r"(?<![\w.])(?P<kind>Tabel|Gambar)\s+(\d+)\.(\d+)(?![\d])"
    r"(?:(\s*(?:-|sampai dengan|sampai|hingga|s\.d\.|dan)\s*)(?:(?P=kind)\s+)?(\d+)\.(\d+)(?![\d]))?")


def live_text(paragraph: etree._Element) -> str:
    """Teks paragraf setelah semua revisi diterima, tanpa penanda field."""
    _, text = paragraph_model(paragraph)
    return text.replace(FIELD, "").replace(BARRIER, "")


def has_drawing(element: etree._Element) -> bool:
    return element.find(f".//{w('drawing')}") is not None or element.find(f".//{w('pict')}") is not None


def body_paragraphs(body: etree._Element) -> list[etree._Element]:
    """Paragraf batang tubuh (bukan di tabel, bukan daftar isi/gambar/tabel), urut dokumen."""
    result = []
    for paragraph in body.iter(w("p")):
        if any(ancestor.tag == w("tbl") for ancestor in paragraph.iterancestors()):
            continue
        if not is_listing_paragraph(paragraph):
            result.append(paragraph)
    return result


def caption_styles(body: etree._Element, styles: StyleBook, config: dict | None = None) -> dict[str, set[str | None]]:
    """Gaya judul tabel dan gambar yang dipakai naskah: judul tepat di atas tabel / di bawah gambar."""
    configured = (config or {}).get("gaya_judul") or {}
    found: dict[str, set[str | None]] = {"Tabel": set(), "Gambar": set()}
    for kind, style in configured.items():
        found.setdefault(kind, set()).add(style)
    children = list(body)
    for index, child in enumerate(children):
        if child.tag != w("p") or is_listing_paragraph(child):
            continue
        match = CAPTION_RE.match(live_text(child))
        if not match or configured.get(match.group(1)):
            continue
        if match.group(1) == "Tabel" and index + 1 < len(children) and children[index + 1].tag == w("tbl"):
            found["Tabel"].add(styles.paragraph_style(child))
        if match.group(1) == "Gambar":
            previous = index - 1
            while previous >= 0 and children[previous].tag == w("p") and not live_text(children[previous]).strip() \
                    and not has_drawing(children[previous]):
                previous -= 1
            if previous >= 0 and has_drawing(children[previous]):
                found["Gambar"].add(styles.paragraph_style(child))
    return found


def is_caption(paragraph: etree._Element, styles: StyleBook, known: dict[str, set[str | None]]) -> re.Match[str] | None:
    match = CAPTION_RE.match(live_text(paragraph))
    if match and styles.paragraph_style(paragraph) in known.get(match.group(1), set()):
        return match
    return None


def seq_instruction(paragraph: etree._Element) -> str | None:
    for element in paragraph.iter(w("instrText")):
        if (element.text or "").strip().startswith("SEQ"):
            return element.text
    return None


def caption_template(body: etree._Element, styles: StyleBook, kind: str, before: etree._Element | None) -> etree._Element | None:
    """Judul contoh: judul sejenis terdekat sebelum titik sisip (utamakan yang bernomor field SEQ)."""
    known = caption_styles(body, styles)
    candidates = []
    for paragraph in body_paragraphs(body):
        if before is not None and paragraph is before:
            break
        match = is_caption(paragraph, styles, known)
        if match and match.group(1) == kind and not paragraph_inserted(paragraph):
            candidates.append(paragraph)
    if not candidates:
        candidates = [paragraph for paragraph in body_paragraphs(body)
                      if (found := is_caption(paragraph, styles, known)) and found.group(1) == kind]
    with_field = [paragraph for paragraph in candidates if seq_instruction(paragraph)]
    pool = with_field or candidates
    return pool[-1] if pool else None


def label_runs(template: etree._Element, kind: str, chapter: str, number: str) -> list[etree._Element]:
    """Run label "Tabel 3." + nomor (field SEQ bila contoh memakainya) meniru judul contoh."""
    runs = [run for run in template.iter(w("r")) if not any(a.tag == w("del") for a in run.iterancestors())]
    text_runs = [run for run in runs if run_kind(run)[0] == "text"]
    label_template = plain_template(text_runs[0]) if text_runs else etree.Element(w("r"))
    instruction = seq_instruction(template)
    if instruction is None:
        return [make_run(label_template, list(f"{kind} {chapter}.{number}"), fresh=True)]
    instruction = re.sub(r"(SEQ\s+\S*?)(\d+)(\.?\s)", lambda m: f"{m.group(1)}{chapter}{m.group(3)}", instruction, count=1)
    field_template = None
    for run in runs:
        if run.find(w("fldChar")) is not None:
            field_template = run
            break
    field_template = plain_template(field_template) if field_template is not None else label_template
    result = [make_run(label_template, list(f"{kind} {chapter}."), fresh=True)]

    def field_run(child: etree._Element) -> etree._Element:
        run = make_run(field_template, [], fresh=True)
        run.append(child)
        return run

    result.append(field_run(etree.Element(w("fldChar"), {w("fldCharType"): "begin"})))
    instr = etree.Element(w("instrText"))
    instr.text = instruction
    instr.set(f"{{{XML_NS}}}space", "preserve")
    result.append(field_run(instr))
    result.append(field_run(etree.Element(w("fldChar"), {w("fldCharType"): "separate"})))
    result.append(make_run(label_template, list(number), fresh=True))
    result.append(field_run(etree.Element(w("fldChar"), {w("fldCharType"): "end"})))
    return result


def normalise_caption(paragraph: etree._Element, body: etree._Element, styles: StyleBook, session: Session) -> bool:
    """Judul tabel/gambar hasil sisipan disamakan dengan judul naskah: gaya paragraf, label tebal, field SEQ."""
    match = CAPTION_RE.match(live_text(paragraph))
    if not match or styles.paragraph_style(paragraph) not in caption_styles(body, styles).get(match.group(1), set()):
        return False
    kind, chapter, number = match.groups()
    template = caption_template(body, styles, kind, paragraph)
    if template is None:
        return False
    properties = clean_properties(template.find(w("pPr")))
    old_properties = paragraph.find(w("pPr"))
    mark = old_properties.find(w("rPr")) if old_properties is not None else None
    if old_properties is not None:
        paragraph.remove(old_properties)
    if mark is not None:
        for element in properties.findall(w("rPr")):
            properties.remove(element)
        place_in_order(properties, mark, PARAGRAPH_PROPERTY_ORDER)
    paragraph.insert(0, properties)
    segments, text = paragraph_model(paragraph)
    plain = [index for index, char in enumerate(text) if char not in (FIELD, BARRIER)]
    label_end = match.end()
    while label_end < len(plain) and text[plain[label_end]] == " ":
        label_end += 1
    runs = isolate(segments, plain[0], plain[label_end - 1] + 1)
    holder = runs[0].getparent()
    anchor = runs[0]
    new_runs = label_runs(template, kind, chapter, number) + [make_run(paragraph_template_run(template), [" "], fresh=True)]
    for run in new_runs:
        anchor.addprevious(run)
    for run in runs:
        run.getparent().remove(run)
    if session.tracked and holder.tag != w("ins"):
        wrapper = etree.Element(w("ins"), revision_attributes(session))
        new_runs[0].addprevious(wrapper)
        wrapper.extend(new_runs)
    return True


# ---------------------------------------------------------------------------
# Gambar: ukuran, salin, ganti.
# ---------------------------------------------------------------------------

def image_size(data: bytes) -> tuple[int, int] | None:
    """Ukuran piksel PNG/JPEG/GIF tanpa pustaka tambahan."""
    if data[:8] == b"\x89PNG\r\n\x1a\n":
        return int.from_bytes(data[16:20], "big"), int.from_bytes(data[20:24], "big")
    if data[:6] in (b"GIF87a", b"GIF89a"):
        return int.from_bytes(data[6:8], "little"), int.from_bytes(data[8:10], "little")
    if data[:2] == b"\xff\xd8":
        index = 2
        while index + 9 < len(data):
            if data[index] != 0xFF:
                index += 1
                continue
            marker = data[index + 1]
            length = int.from_bytes(data[index + 2:index + 4], "big")
            if marker in (0xC0, 0xC1, 0xC2, 0xC3, 0xC5, 0xC6, 0xC7, 0xC9, 0xCA, 0xCB, 0xCD, 0xCE, 0xCF):
                return int.from_bytes(data[index + 7:index + 9], "big"), int.from_bytes(data[index + 5:index + 7], "big")
            index += 2 + length
    return None


def text_area(element: etree._Element) -> tuple[int, int]:
    """Lebar dan tinggi bidang teks (EMU) dari sectPr yang berlaku untuk elemen ini."""
    body = element
    while body.getparent() is not None and body.tag != w("body"):
        body = body.getparent()
    section = None
    node = element
    while node.getparent() is not None and node.getparent() is not body:
        node = node.getparent()
    current = node
    while current is not None:
        found = current.find(f"{w('pPr')}/{w('sectPr')}") if current.tag == w("p") else None
        if current.tag == w("sectPr"):
            found = current
        if found is not None:
            section = found
            break
        current = current.getnext()
    if section is None:
        return 5_040_000, 8_170_000
    size, margin = section.find(w("pgSz")), section.find(w("pgMar"))

    def number(element: etree._Element | None, name: str, default: int) -> int:
        try:
            return int(element.get(w(name))) if element is not None else default
        except (TypeError, ValueError):
            return default

    width = number(size, "w", 11906) - number(margin, "left", 1440) - number(margin, "right", 1440)
    height = number(size, "h", 16838) - number(margin, "top", 1440) - number(margin, "bottom", 1440)
    return width * EMU_PER_TWIP, height * EMU_PER_TWIP


def fitted_extent(width: int, pixels: tuple[int, int], area: tuple[int, int]) -> tuple[int, int]:
    """Pertahankan lebar tampil; tinggi mengikuti rasio gambar baru, tetapi tidak melebihi 80% tinggi halaman."""
    max_width, max_height = area
    width = min(width, max_width)
    height = round(width * pixels[1] / pixels[0])
    limit = int(max_height * 0.8)
    if height > limit:
        width, height = round(limit * pixels[0] / pixels[1]), limit
    return width, height


def set_drawing_extent(drawing: etree._Element, cx: int, cy: int) -> None:
    for extent in drawing.iter(f"{{{WP_NS}}}extent"):
        extent.set("cx", str(cx))
        extent.set("cy", str(cy))
    for ext in drawing.iter(f"{{{A_NS}}}ext"):
        if ext.getparent().tag == f"{{{A_NS}}}xfrm":
            ext.set("cx", str(cx))
            ext.set("cy", str(cy))


def drawing_paragraph_before(paragraph: etree._Element) -> etree._Element | None:
    current = paragraph.getprevious()
    while current is not None:
        if current.tag == w("p") and paragraph_deleted(current) and not live_text(current).strip():
            current = current.getprevious()
            continue
        if current.tag == w("p") and current.find(f".//{w('drawing')}") is not None:
            return current
        if current.tag == w("p") and live_text(current).strip():
            return None
        if current.tag != w("p"):
            return None
        current = current.getprevious()
    return None


def nearest_drawing(body: etree._Element, before: etree._Element) -> etree._Element | None:
    found = None
    for paragraph in body_paragraphs(body):
        if paragraph is before:
            break
        if paragraph.find(f".//{w('drawing')}") is not None:
            found = paragraph
    return found


def build_image_paragraph(body: etree._Element, anchor: etree._Element, image: Path, package: Package,
                          session: Session) -> etree._Element:
    """Paragraf gambar baru: pPr dan bentuk drawing disalin dari gambar terdekat sebelumnya."""
    template = nearest_drawing(body, anchor)
    if template is None:
        raise ValueError("tidak ada gambar contoh di naskah")
    data = image.read_bytes()
    pixels = image_size(data)
    if not pixels:
        raise ValueError(f"format gambar tidak dikenali: {image.name}")
    paragraph = etree.Element(w("p"))
    properties = clean_properties(template.find(w("pPr")))
    if len(properties):
        paragraph.append(properties)
    source_run = next(run for run in template.iter(w("r")) if run.find(w("drawing")) is not None)
    run = deepcopy(source_run)
    for child in list(run):
        if child.tag not in (w("rPr"), w("drawing")):
            run.remove(child)
    drawing = run.find(w("drawing"))
    rel_id = package.add_media(data, image.suffix, image.stem)
    for blip in drawing.iter(f"{{{A_NS}}}blip"):
        blip.set(f"{{{R_NS}}}embed", rel_id)
        for attribute in (f"{{{R_NS}}}link",):
            blip.attrib.pop(attribute, None)
    extent = drawing.find(f".//{{{WP_NS}}}extent")
    width = int(extent.get("cx")) if extent is not None else text_area(anchor)[0]
    set_drawing_extent(drawing, *fitted_extent(width, pixels, text_area(anchor)))
    for doc_properties in drawing.iter(f"{{{WP_NS}}}docPr"):
        doc_properties.set("id", str(session.next_drawing_id))
        session.next_drawing_id += 1
        doc_properties.set("name", image.name)
        doc_properties.set("descr", image.name)
        doc_properties.attrib.pop("title", None)
    for picture in drawing.iter(f"{{{PIC_NS}}}cNvPr"):
        picture.set("name", image.name)
    paragraph.append(run)
    mark_paragraph_inserted(paragraph, session)
    return paragraph


def replace_image(caption: etree._Element, image: Path, package: Package, session: Session) -> str:
    """Ganti gambar di atas judul: lebar tampil tetap, tinggi mengikuti rasio gambar baru."""
    figure = drawing_paragraph_before(caption)
    if figure is None:
        raise ValueError("paragraf gambar di atas judul tidak ditemukan")
    data = image.read_bytes()
    pixels = image_size(data)
    if not pixels:
        raise ValueError(f"format gambar tidak dikenali: {image.name}")
    drawing = figure.find(f".//{w('drawing')}")
    blip = drawing.find(f".//{{{A_NS}}}blip")
    old_rel = blip.get(f"{{{R_NS}}}embed")
    rel_id = package.add_media(data, image.suffix, image.stem)
    blip.set(f"{{{R_NS}}}embed", rel_id)
    extent = drawing.find(f".//{{{WP_NS}}}extent")
    old_size = (int(extent.get("cx")), int(extent.get("cy")))
    new_size = fitted_extent(old_size[0], pixels, text_area(figure))
    set_drawing_extent(drawing, *new_size)
    still_used = any(element.get(f"{{{R_NS}}}embed") == old_rel for element in package.root("word/document.xml").iter(f"{{{A_NS}}}blip"))
    if old_rel and not still_used:
        rels = package.relationships()
        for rel in list(rels):
            if rel.get("Id") == old_rel:
                package.files.pop("word/" + rel.get("Target").lstrip("/").removeprefix("word/"), None)
                rels.remove(rel)
    cm = lambda emu: f"{emu / 360000:.1f}".replace(".", ",")
    return f"{image.name} ({cm(new_size[0])} × {cm(new_size[1])} cm, sebelumnya {cm(old_size[0])} × {cm(old_size[1])} cm)"

# ---------------------------------------------------------------------------
# Operasi struktural per butir revisi.
# ---------------------------------------------------------------------------

@dataclass
class Workspace:
    """Semua yang dibutuhkan operasi struktural."""

    parts: dict[str, etree._Element]
    package: Package
    styles: StyleBook
    session: Session
    sources: dict[str, dict] = field(default_factory=dict)
    markers: dict[str, list[str]] = field(default_factory=dict)

    @property
    def body(self) -> etree._Element:
        return self.parts["word/document.xml"].find(w("body"))


def unique_paragraph(workspace: Workspace, cari: str) -> etree._Element:
    pattern = compile_pattern(cari)
    matches = find_matches({"word/document.xml": workspace.parts["word/document.xml"]}, pattern)
    paragraphs = list(dict.fromkeys(match.paragraph for match in matches))
    if not paragraphs:
        raise ValueError(f"paragraf contoh tidak ditemukan: {cari!r}")
    if len(paragraphs) > 1:
        raise ValueError(f"paragraf contoh ambigu ({len(paragraphs)} tempat): {cari!r}")
    return paragraphs[0]


def style_matches(workspace: Workspace, paragraph: etree._Element, style: str) -> bool:
    actual = workspace.styles.paragraph_style(paragraph)
    if style.lower() == "normal":
        return actual in (None, workspace.styles.default_paragraph)
    return actual == style


def nearest_with_style(workspace: Workspace, anchor: etree._Element, style: str) -> etree._Element | None:
    """Paragraf berisi teks bergaya sama yang paling dekat sebelum (atau sama dengan) jangkar."""
    found = None
    for paragraph in workspace.body.iter(w("p")):
        if is_listing_paragraph(paragraph) or paragraph_deleted(paragraph):
            if paragraph is anchor:
                break
            continue
        if style_matches(workspace, paragraph, style) and live_text(paragraph).strip():
            found = paragraph
        if paragraph is anchor:
            break
    return found


def table_after(paragraph: etree._Element) -> etree._Element | None:
    if any(ancestor.tag == w("tbl") for ancestor in paragraph.iterancestors()):
        return next(ancestor for ancestor in paragraph.iterancestors() if ancestor.tag == w("tbl"))
    current = paragraph.getnext()
    while current is not None and current.tag == w("p") and not live_text(current).strip():
        current = current.getnext()
    return current if current is not None and current.tag == w("tbl") else None


def resolve_examples(revision: Revision, workspace: Workspace) -> None:
    """Paragraf contoh ("seperti") dicari di naskah asli, sebelum ada butir yang mengubah teks."""
    entries = revision.extra.get("sisip_paragraf") or []
    resolved = []
    for entry in entries if isinstance(entries, list) else [entries]:
        entry = {"teks": entry} if isinstance(entry, str) else dict(entry)
        if entry.get("seperti"):
            entry["_contoh"] = unique_paragraph(workspace, entry["seperti"])
        resolved.append(entry)
    revision.extra["_sisip"] = resolved


def op_insert_paragraphs(revision: Revision, workspace: Workspace) -> tuple[list[etree._Element], str]:
    anchor = revision.target.paragraph
    session = workspace.session
    created: list[etree._Element] = []
    before = bool(revision.extra.get("sebelum"))
    item_style = revision.extra.get("gaya")
    tables = 0
    for entry in revision.extra["_sisip"]:
        style = entry.get("gaya") or item_style
        if "tabel" in entry:
            example = entry.get("_contoh")
            table = table_after(example) if example is not None else None
            if table is None:
                raise ValueError("isi 'seperti' dengan judul atau isi tabel contoh")
            element = build_table(table, entry["tabel"], session)
            if entry.get("lebar"):
                set_column_widths(element, entry["lebar"])
            tables += 1
        elif "gambar" in entry:
            image = session.resolve_path(entry["gambar"])
            if not image.exists():
                print(f"   ({revision.id}: gambar {image} belum tersedia; paragraf gambar dilewati)")
                continue
            element = build_image_paragraph(workspace.body, anchor, image, workspace.package, session)
        else:
            source = entry.get("_contoh")
            if source is None and style:
                source = nearest_with_style(workspace, anchor, style)
            if source is None:
                source = anchor
            element = build_paragraph(source, entry.get("teks", ""), session,
                                      style=style if source is anchor and style else None)
        created.append(element)
    insert_elements(anchor, created, session, before=before)
    for element in created:
        if element.tag == w("p"):
            normalise_caption(element, workspace.body, workspace.styles, session)
    paragraphs = len(created) - tables
    return created, f"{paragraphs} paragraf" + (f" dan {tables} tabel" if tables else "") + " disisipkan"


def op_insert_rows(revision: Revision, workspace: Workspace) -> tuple[list[etree._Element], str]:
    paragraph = revision.target.paragraph
    row = next((ancestor for ancestor in paragraph.iterancestors() if ancestor.tag == w("tr")), None)
    if row is None:
        raise ValueError("'cari' untuk sisip_baris harus berada di dalam sel tabel")
    rows = [clone_row(row, [str(value) for value in values], workspace.session) for values in revision.extra["sisip_baris"]]
    insert_elements(row, rows, workspace.session)
    return rows, f"{len(rows)} baris tabel disisipkan"


def op_delete(revision: Revision, workspace: Workspace) -> tuple[list[etree._Element], str]:
    mode = revision.extra["hapus"]
    paragraph = revision.target.paragraph
    done = []
    affected: list[etree._Element] = []
    if "tabel" in mode:
        table = table_after(paragraph)
        if table is None:
            raise ValueError("tidak ada tabel sesudah judul ini")
        delete_table(table, workspace.session)
        done.append(f"tabel ({len(table.findall(w('tr')))} baris)")
        affected.append(table)
    if "paragraf" in mode:
        affected.insert(0, paragraph)
        delete_paragraph(paragraph, workspace.session)
        done.insert(0, "paragraf")
    return affected[:1], " dan ".join(done) + " dihapus"


def op_replace_image(revision: Revision, workspace: Workspace) -> tuple[list[etree._Element], str]:
    image = workspace.session.resolve_path(revision.extra["ganti_gambar"])
    if not image.exists():
        print(f"   ({revision.id}: gambar {image} belum tersedia; penggantian dilewati)")
        return [], SKIPPED
    description = replace_image(revision.target.paragraph, image, workspace.package, workspace.session)
    revision.extra["_komentar_gambar"] = f"Gambar diganti dengan {description}. Penggantian gambar tidak tercatat sebagai Track Changes."
    return [revision.target.paragraph], f"gambar diganti: {description}"


def op_number_format(revision: Revision, workspace: Workspace) -> tuple[list[etree._Element], str]:
    """Ganti format nomor otomatis satu daftar (mis. judul 3.13.%1 menjadi 3.11.%1) lewat pPrChange."""
    paragraph = revision.target.paragraph
    level_format = revision.extra["format_nomor"]
    number_properties = paragraph.find(f"{w('pPr')}/{w('numPr')}")
    if number_properties is None or number_properties.find(w("numId")) is None:
        raise ValueError("paragraf ini tidak memakai penomoran otomatis")
    old_id = number_properties.find(w("numId")).get(w("val"))
    level_element = number_properties.find(w("ilvl"))
    level = level_element.get(w("val")) if level_element is not None else "0"
    numbering = workspace.package.numbering()
    num = next((item for item in numbering.findall(w("num")) if item.get(w("numId")) == old_id), None)
    if num is None:
        raise ValueError(f"numId {old_id} tidak ada di numbering.xml")
    abstract_id = num.find(w("abstractNumId")).get(w("val"))
    abstract = next(item for item in numbering.findall(w("abstractNum")) if item.get(w("abstractNumId")) == abstract_id)
    new_abstract = deepcopy(abstract)
    new_abstract_id = str(max(int(item.get(w("abstractNumId"))) for item in numbering.findall(w("abstractNum"))) + 1)
    new_abstract.set(w("abstractNumId"), new_abstract_id)
    fresh_nsid(new_abstract, f"{revision.id}{level_format}")
    for element in new_abstract.findall(w("tmpl")):
        new_abstract.remove(element)
    target_level = next((item for item in new_abstract.findall(w("lvl")) if item.get(w("ilvl")) == level), None)
    if target_level is None:
        raise ValueError(f"level {level} tidak ada pada definisi daftar")
    text = target_level.find(w("lvlText"))
    old_format = text.get(w("val"))
    text.set(w("val"), level_format)
    numbering.findall(w("abstractNum"))[-1].addnext(new_abstract)
    new_num_id = str(max(int(item.get(w("numId"))) for item in numbering.findall(w("num"))) + 1)
    new_num = deepcopy(num)
    new_num.set(w("numId"), new_num_id)
    new_num.find(w("abstractNumId")).set(w("val"), new_abstract_id)
    numbering.findall(w("num"))[-1].addnext(new_num)
    changed = 0
    for candidate in workspace.body.iter(w("p")):
        candidate_number = candidate.find(f"{w('pPr')}/{w('numPr')}/{w('numId')}")
        if candidate_number is None or candidate_number.get(w("val")) != old_id:
            continue
        if workspace.session.tracked and not paragraph_inserted(candidate):
            record_paragraph_change(candidate, workspace.session)
        candidate.find(f"{w('pPr')}/{w('numPr')}/{w('numId')}").set(w("val"), new_num_id)
        changed += 1
    return [paragraph], f"format nomor {old_format} → {level_format} pada {changed} paragraf"


def fresh_nsid(abstract: etree._Element, seed: str) -> None:
    import hashlib
    nsid = abstract.find(w("nsid"))
    value = hashlib.sha1(seed.encode("utf-8")).hexdigest()[:8].upper()
    if nsid is None:
        nsid = etree.Element(w("nsid"))
        abstract.insert(0, nsid)
    nsid.set(w("val"), value)


def record_paragraph_change(paragraph: etree._Element, session: Session) -> None:
    """Simpan pPr lama sebagai pPrChange agar perubahan format paragraf bisa di-Reject."""
    properties = paragraph.find(w("pPr"))
    if properties is None:
        properties = etree.Element(w("pPr"))
        paragraph.insert(0, properties)
    if properties.find(w("pPrChange")) is not None:
        return
    previous = etree.Element(w("pPr"))
    for child in properties:
        if child.tag not in (w("rPr"), w("sectPr"), w("pPrChange")):
            previous.append(deepcopy(child))
    change = etree.Element(w("pPrChange"), revision_attributes(session))
    change.append(previous)
    place_in_order(properties, change, PARAGRAPH_PROPERTY_ORDER)

# ---------------------------------------------------------------------------
# sisip_dokumen: salin satu bagian dari .docx lain.
# ---------------------------------------------------------------------------

def load_source(workspace: Workspace, path: Path) -> dict:
    key = str(path.resolve())
    if key not in workspace.sources:
        with zipfile.ZipFile(path) as archive:
            files = {name: archive.read(name) for name in archive.namelist() if not name.endswith("/")}
        source = {"files": files, "document": etree.fromstring(files["word/document.xml"]),
                  "styles": etree.fromstring(files["word/styles.xml"]) if "word/styles.xml" in files else None,
                  "numbering": etree.fromstring(files["word/numbering.xml"]) if "word/numbering.xml" in files else None,
                  "rels": etree.fromstring(files["word/_rels/document.xml.rels"]) if "word/_rels/document.xml.rels" in files else None,
                  "style_map": {}, "num_map": {}, "rel_map": {}}
        workspace.sources[key] = source
    return workspace.sources[key]


def section_elements(source: dict, marker: str, markers: list[str], skip_marker: bool) -> list[etree._Element]:
    """Elemen dari paragraf yang diawali penanda sampai sebelum penanda lain (atau akhir dokumen)."""
    children = [child for child in source["document"].find(w("body")) if child.tag != w("sectPr")]

    def starts(child: etree._Element, prefix: str) -> bool:
        return child.tag == w("p") and paragraph_plain_text(child).strip().startswith(prefix)

    start = next((index for index, child in enumerate(children) if starts(child, marker)), None)
    if start is None:
        raise ValueError(f"penanda bagian {marker!r} tidak ada di dokumen sisipan")
    end = len(children)
    for index in range(start + 1, len(children)):
        if any(starts(children[index], other) for other in markers if other != marker):
            end = index
            break
    return children[start + (1 if skip_marker else 0):end]


def import_style(workspace: Workspace, source: dict, style_id: str) -> str:
    """Pakai gaya naskah yang sama (id atau nama); bila tidak ada, salin definisinya dari dokumen sumber."""
    cache = source["style_map"]
    if style_id in cache:
        return cache[style_id]
    target = workspace.styles.styles
    if style_id in target:
        cache[style_id] = style_id
        return style_id
    source_style = None
    if source["styles"] is not None:
        source_style = next((style for style in source["styles"].iter(w("style")) if style.get(w("styleId")) == style_id), None)
    if source_style is not None:
        name = source_style.find(w("name"))
        name = (name.get(w("val")) if name is not None else "").lower()
        for identifier, style in target.items():
            existing = style.find(w("name"))
            if name and existing is not None and existing.get(w("val"), "").lower() == name:
                cache[style_id] = identifier
                return identifier
    cache[style_id] = style_id
    if source_style is None:
        return style_id
    copy = deepcopy(source_style)
    workspace.package.styles().append(copy)
    target[style_id] = copy
    for reference in ("basedOn", "next", "link"):
        element = copy.find(w(reference))
        if element is not None:
            mapped = import_style(workspace, source, element.get(w("val")))
            if mapped in target:
                element.set(w("val"), mapped)
            else:
                copy.remove(element)
    return style_id


def import_numbering(workspace: Workspace, source: dict, num_id: str) -> str:
    """Salin definisi daftar bernomor (num + abstractNum) dengan id baru."""
    cache = source["num_map"]
    if num_id in cache:
        return cache[num_id]
    if source["numbering"] is None or num_id == "0":
        cache[num_id] = num_id
        return num_id
    num = next((item for item in source["numbering"].findall(w("num")) if item.get(w("numId")) == num_id), None)
    if num is None:
        cache[num_id] = "0"
        return "0"
    abstract_id = num.find(w("abstractNumId")).get(w("val"))
    abstract = next(item for item in source["numbering"].findall(w("abstractNum")) if item.get(w("abstractNumId")) == abstract_id)
    numbering = workspace.package.numbering()
    abstracts = numbering.findall(w("abstractNum"))
    nums = numbering.findall(w("num"))
    new_abstract_id = str(max([int(item.get(w("abstractNumId"))) for item in abstracts] + [-1]) + 1)
    new_num_id = str(max([int(item.get(w("numId"))) for item in nums] + [0]) + 1)
    new_abstract = deepcopy(abstract)
    new_abstract.set(w("abstractNumId"), new_abstract_id)
    fresh_nsid(new_abstract, f"{num_id}-{new_abstract_id}")
    for level in new_abstract.iter(w("pStyle")):
        level.set(w("val"), import_style(workspace, source, level.get(w("val"))))
    if abstracts:
        abstracts[-1].addnext(new_abstract)
    elif nums:
        nums[0].addprevious(new_abstract)
    else:
        numbering.append(new_abstract)
    new_num = deepcopy(num)
    new_num.set(w("numId"), new_num_id)
    new_num.find(w("abstractNumId")).set(w("val"), new_abstract_id)
    if nums:
        nums[-1].addnext(new_num)
    else:
        new_abstract.addnext(new_num)
    cache[num_id] = new_num_id
    return new_num_id


def import_relationship(workspace: Workspace, source: dict, rel_id: str) -> str | None:
    cache = source["rel_map"]
    if rel_id in cache:
        return cache[rel_id]
    rel = None
    if source["rels"] is not None:
        rel = next((item for item in source["rels"] if item.get("Id") == rel_id), None)
    if rel is None:
        cache[rel_id] = None
        return None
    rel_type, target = rel.get("Type"), rel.get("Target")
    if rel.get("TargetMode") == "External":
        new_id = workspace.package.add_relationship(rel_type, target, external=True)
    elif rel_type == IMAGE_REL:
        name = "word/" + target.lstrip("/").removeprefix("word/")
        data = source["files"][name]
        stem, _, extension = Path(name).name.rpartition(".")
        new_id = workspace.package.add_media(data, extension or "png", "sisipan")
    else:
        new_id = None
    cache[rel_id] = new_id
    return new_id


def prepare_imported(element: etree._Element, workspace: Workspace, source: dict) -> etree._Element:
    copy = deepcopy(element)
    for unwanted in list(copy.iter(w("bookmarkStart"), w("bookmarkEnd"), w("commentRangeStart"), w("commentRangeEnd"), w("proofErr"))):
        unwanted.getparent().remove(unwanted)
    for reference in list(copy.iter(w("commentReference"))):
        run = reference.getparent()
        run.getparent().remove(run)
    for node in copy.iter():
        for attribute in list(node.attrib):
            if attribute.startswith(f"{{{W14_NS}}}"):
                del node.attrib[attribute]
    for tag in ("pStyle", "rStyle", "tblStyle"):
        for node in copy.iter(w(tag)):
            node.set(w("val"), import_style(workspace, source, node.get(w("val"))))
    for node in copy.iter(w("numId")):
        if node.getparent().tag == w("numPr"):
            node.set(w("val"), import_numbering(workspace, source, node.get(w("val"))))
    for node in copy.iter():
        for attribute in list(node.attrib):
            if attribute.startswith(f"{{{R_NS}}}"):
                new_id = import_relationship(workspace, source, node.get(attribute))
                if new_id is None:
                    del node.attrib[attribute]
                else:
                    node.set(attribute, new_id)
    for doc_properties in copy.iter(f"{{{WP_NS}}}docPr"):
        doc_properties.set("id", str(workspace.session.next_drawing_id))
        workspace.session.next_drawing_id += 1
    return copy


def heading_level(workspace: Workspace, paragraph: etree._Element) -> int | None:
    direct = paragraph.find(f"{w('pPr')}/{w('outlineLvl')}")
    if direct is not None:
        return int(direct.get(w("val")))
    style_id = workspace.styles.paragraph_style(paragraph)
    seen = 0
    while style_id and style_id in workspace.styles.styles and seen < 15:
        style = workspace.styles.styles[style_id]
        level = style.find(f"{w('pPr')}/{w('outlineLvl')}")
        if level is not None:
            return int(level.get(w("val")))
        based = style.find(w("basedOn"))
        style_id = based.get(w("val")) if based is not None else None
        seen += 1
    return None


TYPED_HEADING_NUMBER = re.compile(r"^\s*\d+(?:\.\d+)+\.?\s+")


def adopt_heading_numbering(paragraph: etree._Element, workspace: Workspace) -> bool:
    """Judul bernomor ketikan ("3.7.3 Skenario") ikut penomoran otomatis judul setingkat di subbab yang sama."""
    level = heading_level(workspace, paragraph)
    text = live_text(paragraph)
    typed = TYPED_HEADING_NUMBER.match(text)
    if level is None or level > 8 or not typed:
        return False
    style = workspace.styles.paragraph_style(paragraph)
    current = paragraph.getprevious()
    template = None
    while current is not None:
        if current.tag == w("p") and not paragraph_deleted(current):
            current_level = heading_level(workspace, current)
            if current_level is not None and current_level < level and live_text(current).strip():
                break
            if workspace.styles.paragraph_style(current) == style and current.find(f"{w('pPr')}/{w('numPr')}") is not None:
                template = current
                break
        current = current.getprevious()
    if template is None:
        return False
    segments, raw = paragraph_model(paragraph)
    plain = [index for index, char in enumerate(raw) if char not in (FIELD, BARRIER)]
    for run in isolate(segments, plain[0], plain[typed.end() - 1] + 1):
        run.getparent().remove(run)
    properties = clean_properties(template.find(w("pPr")))
    old = paragraph.find(w("pPr"))
    mark = old.find(w("rPr")) if old is not None else None
    if old is not None:
        paragraph.remove(old)
    for element in properties.findall(w("rPr")):
        properties.remove(element)
    if mark is not None:
        place_in_order(properties, mark, PARAGRAPH_PROPERTY_ORDER)
    paragraph.insert(0, properties)
    return True


def op_insert_document(revision: Revision, workspace: Workspace) -> tuple[list[etree._Element], str]:
    settings = revision.extra["sisip_dokumen"]
    path = workspace.session.resolve_path(settings["file"])
    if not path.exists():
        raise ValueError(f"berkas sisipan {path} tidak ada")
    source = load_source(workspace, path)
    sections = settings["bagian"] if isinstance(settings["bagian"], list) else [settings["bagian"]]
    markers = list(dict.fromkeys(settings.get("penanda", []) + workspace.markers.get(str(path.resolve()), []) + sections))
    created: list[etree._Element] = []
    for section in sections:
        for element in section_elements(source, section, markers, bool(settings.get("tanpa_penanda"))):
            created.append(prepare_imported(element, workspace, source))
    context = f"dokumen:{path.name}"
    insert_elements(revision.target.paragraph, created, workspace.session, before=bool(revision.extra.get("sebelum")), context=context)
    headings = captions = 0
    for element in created:
        if element.tag == w("tbl"):
            mark_table_inserted(element, workspace.session)
        elif element.tag == w("p"):
            mark_paragraph_inserted(element, workspace.session)
    for element in created:
        if element.tag != w("p"):
            continue
        if settings.get("judul_otomatis") and adopt_heading_numbering(element, workspace):
            headings += 1
        elif normalise_caption(element, workspace.body, workspace.styles, workspace.session):
            captions += 1
    tables = sum(1 for element in created if element.tag == w("tbl"))
    images = sum(1 for element in created for _ in element.iter(w("drawing")))
    detail = f"{len(created) - tables} paragraf, {tables} tabel, {images} gambar dari {path.name} ({', '.join(sections)})"
    if headings:
        detail += f"; {headings} judul ikut nomor otomatis"
    return created, detail

# ---------------------------------------------------------------------------
# Penomoran ulang judul tabel/gambar dan rujukannya di teks.
# ---------------------------------------------------------------------------

@dataclass
class Caption:
    paragraph: etree._Element
    kind: str
    chapter: str
    old: str
    context: str
    position: int
    title: str
    span: tuple[int, int]
    new: str = ""


def plain_positions(text: str) -> tuple[str, list[int]]:
    positions = [index for index, char in enumerate(text) if char != FIELD]
    return "".join(text[index] for index in positions).translate(NORMALIZE), positions


def set_span_text(paragraph: etree._Element, start: int, end: int, value: str, session: Session) -> None:
    """Ganti teks [start, end) paragraf; langsung bila teks itu hasil sisipan, selain itu sebagai Track Changes."""
    segments, _ = paragraph_model(paragraph)
    runs = isolate(segments, start, end)
    if not session.tracked or all(inside_insertion(run) for run in runs):
        first = runs[0]
        for element in list(first):
            if element.tag != w("rPr"):
                first.remove(element)
        first.extend(text_elements(list(value)))
        for run in runs[1:]:
            run.getparent().remove(run)
        return
    replace_span(runs, [(value, False)], session)


def collect_captions(body: etree._Element, styles: StyleBook, session: Session, config: dict) -> list[Caption]:
    known = caption_styles(body, styles, config)
    order = {element: index for index, element in enumerate(body.iter())}
    captions = []
    for paragraph in body_paragraphs(body):
        if not is_caption(paragraph, styles, known):
            continue
        _, raw = paragraph_model(paragraph)
        plain, positions = plain_positions(raw)
        match = CAPTION_RE.match(plain.replace(BARRIER, " "))
        if not match:
            continue
        span = (positions[match.start(3)], positions[match.end(3) - 1] + 1)
        captions.append(Caption(paragraph, match.group(1), match.group(2), match.group(3), session.context_of(paragraph),
                                order[paragraph], plain[match.end():].strip(), span))
    return captions


def renumber_captions(body: etree._Element, styles: StyleBook, session: Session, config: dict) -> list[str]:
    """Nomori ulang judul tabel/gambar per bab sesuai urutan dokumen, lalu sesuaikan rujukan di teks."""
    captions = collect_captions(body, styles, session, config)
    counters: dict[tuple[str, str], int] = {}
    for caption in captions:
        key = (caption.kind, caption.chapter)
        counters[key] = counters.get(key, 0) + 1
        caption.new = str(counters[key])
    by_context: dict[str, dict[tuple[str, str, str], list[Caption]]] = {}
    for caption in captions:
        by_context.setdefault(caption.context, {}).setdefault((caption.kind, caption.chapter, caption.old), []).append(caption)
    order = {element: index for index, element in enumerate(body.iter())}
    caption_ids = {id(caption.paragraph) for caption in captions}
    unresolved: set[str] = set()

    def resolve(kind: str, chapter: str, number: str, context: str, position: int) -> str | None:
        key = (kind, chapter, number)
        candidates = by_context.get(context, {}).get(key) or by_context.get("naskah", {}).get(key)
        if not candidates:
            unresolved.add(f"{kind} {chapter}.{number}")
            return None
        return min(candidates, key=lambda caption: abs(caption.position - position)).new

    edits = 0
    for paragraph in list(body.iter(w("p"))):
        if is_listing_paragraph(paragraph) or id(paragraph) in caption_ids:
            continue
        _, raw = paragraph_model(paragraph)
        if "Tabel" not in raw and "Gambar" not in raw:
            continue
        plain, positions = plain_positions(raw)
        context = session.context_of(paragraph)
        position = order[paragraph]
        changes = []
        for match in REFERENCE_RE.finditer(plain):
            kind = match.group(1)
            for chapter_group, number_group in ((2, 3), (5, 6)):
                if match.group(number_group) is None:
                    continue
                new = resolve(kind, match.group(chapter_group), match.group(number_group), context, position)
                if new is not None and new != match.group(number_group):
                    changes.append((positions[match.start(number_group)], positions[match.end(number_group) - 1] + 1, new))
        for start, end, new in sorted(changes, reverse=True):
            set_span_text(paragraph, start, end, new, session)
            edits += 1
    lines = []
    for caption in captions:
        if caption.new != caption.old:
            set_span_text(caption.paragraph, caption.span[0], caption.span[1], caption.new, session)
        source = "" if caption.context == "naskah" else f" [{caption.context}]"
        arrow = "→" if caption.new != caption.old else "="
        title = caption.title.replace(BARRIER, "").strip()
        lines.append(f"  {caption.kind} {caption.chapter}.{caption.old} {arrow} {caption.chapter}.{caption.new}{source}  {title[:70]}")
    lines.append(f"  rujukan di teks diperbarui: {edits}")
    if unresolved:
        lines.append("  rujukan tanpa judul yang cocok (dibiarkan): " + ", ".join(sorted(unresolved)))
    return lines


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


HAPUS_MODES = ("paragraf", "tabel", "paragraf+tabel")


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
        extra = {key: value for key, value in item.items()
                 if key in STRUCTURAL_KEYS or key in ("sebelum", "gaya", "tegak")}
        if item.get("ganti") is None and not item.get("catatan") and not any(key in extra for key in STRUCTURAL_KEYS):
            sys.exit(f"butir {identifier}: isi 'ganti', 'catatan', atau salah satu dari {', '.join(STRUCTURAL_KEYS)}")
        if item.get("ganti") is not None and "\n" in item["ganti"]:
            sys.exit(f"butir {identifier}: 'ganti' tidak boleh berisi baris baru; pecah menjadi butir per paragraf")
        if "hapus" in extra and extra["hapus"] not in HAPUS_MODES:
            sys.exit(f"butir {identifier}: 'hapus' harus salah satu dari {', '.join(HAPUS_MODES)}")
        if "sisip_dokumen" in extra and not {"file", "bagian"} <= set(extra["sisip_dokumen"]):
            sys.exit(f"butir {identifier}: 'sisip_dokumen' butuh 'file' dan 'bagian'")
        urutan = item.get("urutan")
        if urutan is not None and (not isinstance(urutan, int) or urutan < 1):
            sys.exit(f"butir {identifier}: 'urutan' harus bilangan bulat mulai 1")
        revisions.append(Revision(
            id=identifier, cari=item["cari"], lokasi=item.get("lokasi", ""),
            ganti=item.get("ganti"), catatan=item.get("catatan"), semua=bool(item.get("semua")),
            urutan=urutan, utuh=bool(item.get("utuh")), tetap_komentar=bool(item.get("tetap_komentar")), extra=extra,
        ))
    return revisions


SKIPPED = "dilewati"

STRUCTURAL_OPERATIONS = [
    ("hapus", op_delete),
    ("sisip_baris", op_insert_rows),
    ("sisip_paragraf", op_insert_paragraphs),
    ("sisip_dokumen", op_insert_document),
    ("ganti_gambar", op_replace_image),
]


def pin_target(revision: Revision, parts: dict[str, etree._Element], pattern: re.Pattern[str], arguments: argparse.Namespace) -> None:
    """Tetapkan paragraf sasaran pada naskah asli (sebelum butir lain mengubah teks)."""
    scope = parts if not revision.structural else {"word/document.xml": parts["word/document.xml"]}
    matches = find_matches(scope, pattern, whole=revision.utuh)
    if not matches:
        revision.status = "tidak ditemukan"
        return
    if revision.urutan is not None:
        if revision.urutan > len(matches):
            revision.status = "tidak ditemukan"
            revision.detail = f"urutan {revision.urutan}, hanya ada {len(matches)} tempat"
            return
        revision.target = matches[revision.urutan - 1]
        return
    if len(matches) > 1 and not revision.semua and not arguments.pertama:
        revision.status = f"ambigu ({len(matches)} tempat)"
        revision.detail = " | ".join(match.snippet for match in matches[:3])
        return
    revision.target = matches[0]


def apply_structural(revision: Revision, workspace: Workspace, with_comments: bool, operations) -> None:
    created: list[etree._Element] = []
    details: list[str] = []
    try:
        for key, operation in operations:
            if key in revision.extra:
                elements, detail = operation(revision, workspace)
                created.extend(elements)
                details.append(detail)
    except (ValueError, KeyError, OSError) as error:
        revision.status, revision.detail = "galat", str(error)
        return
    details = [detail for detail in details if detail != SKIPPED]
    if not details:
        if revision.status in ("", ALREADY_DONE):
            revision.status = "dilewati (gambar belum tersedia)"
        return
    revision.applied += 1
    if revision.status in ("", ALREADY_DONE, "diterapkan"):
        revision.status = "diterapkan"
    revision.detail = "; ".join(filter(None, [revision.detail] + details))
    notes = []
    if with_comments and revision.catatan:
        notes.append(f"[{revision.id}] {revision.catatan}")
    if with_comments and revision.extra.get("_komentar_gambar"):
        notes.append(f"[{revision.id}] {revision.extra.pop('_komentar_gambar')}")
    if notes and created:
        attach_comment_to_block(created, "\n".join(notes), workspace.session)


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
    package = Package(files)
    package.roots.update(parts)

    comments_name = comments_part_name(files)
    existing_comments = files.get(comments_name)
    comment_root = etree.fromstring(existing_comments) if existing_comments else None
    document_root = parts["word/document.xml"]
    session = Session(
        author=arguments.penulis,
        date=datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        tracked=not arguments.langsung,
        next_change_id=max_numeric_id(list(parts.values()), ("ins", "del", "moveFrom", "moveTo", "rPrChange", "pPrChange")) + 1,
        next_comment_id=max(max_numeric_id([comment_root], ("comment",)) if comment_root is not None else -1,
                            max_numeric_id(list(parts.values()), ("commentRangeStart", "commentReference"))) + 1,
        next_drawing_id=max([int(element.get("id")) for element in document_root.iter(f"{{{WP_NS}}}docPr")
                             if (element.get("id") or "").isdigit()] + [0]) + 1,
        base_dirs=[source.resolve().parent, Path(arguments.revisi).resolve().parent, Path.cwd()],
    )
    styles = StyleBook(package.root("word/styles.xml") if "word/styles.xml" in files else None)
    ITALIC_CHARACTER_STYLES.update(identifier for identifier, style in styles.styles.items()
                                   if style.get(w("type")) == "character" and styles.lookup(identifier, f"{w('rPr')}/{w('i')}"))
    workspace = Workspace(parts, package, styles, session)
    with_comments = not arguments.tanpa_komentar
    if arguments.tanpa_alasan:
        for revision in revisions:
            if (revision.ganti is not None or revision.structural) and not revision.tetap_komentar:
                revision.catatan = None

    patterns: dict[str, re.Pattern[str]] = {}
    for revision in revisions:
        try:
            patterns[revision.id] = compile_pattern(revision.cari)
            if revision.ganti is not None:
                parse_styled(revision.ganti)
        except ValueError as error:
            revision.status, revision.detail = "galat", str(error)
            continue
        if revision.pinned:
            pin_target(revision, parts, patterns[revision.id], arguments)
            if revision.target is not None and revision.structural:
                try:
                    resolve_examples(revision, workspace)
                except ValueError as error:
                    revision.status, revision.detail, revision.target = "galat", str(error), None
        settings = revision.extra.get("sisip_dokumen")
        if settings:
            key = str(session.resolve_path(settings["file"]).resolve())
            sections = settings["bagian"] if isinstance(settings["bagian"], list) else [settings["bagian"]]
            workspace.markers.setdefault(key, []).extend(sections)

    for revision in revisions:
        if revision.status or revision.id not in patterns:
            continue
        if revision.ganti is None and (revision.structural or not with_comments):
            if not revision.structural:
                revision.status = "dilewati (komentar dimatikan)"
            continue
        pattern = patterns[revision.id]
        if revision.target is not None:
            matches = find_matches({revision.target.part: parts[revision.target.part]}, pattern,
                                   whole=revision.utuh, only=revision.target.paragraph)[:1]
            if not matches:
                revision.status = "tidak ditemukan"
                revision.detail = "teks sasaran sudah diubah butir sebelumnya"
                continue
        else:
            matches = find_matches(parts, pattern, whole=revision.utuh)
        if not matches:
            revision.status = "tidak ditemukan"
            continue
        if len(matches) > 1 and not revision.semua and not arguments.pertama:
            revision.status = f"ambigu ({len(matches)} tempat)"
            revision.detail = " | ".join(match.snippet for match in matches[:3])
            continue
        targets = matches if revision.semua else matches[:1]
        notes: list[str] = []
        already = 0
        comment_here = with_comments and not revision.structural
        for match in sorted(targets, key=lambda item: item.start, reverse=True):
            note = apply_match(match, revision, session, comment_here)
            if note == ALREADY_DONE:
                already += 1
                continue
            if note and not note.startswith("diterapkan"):
                notes.append(note)
                continue
            if note:
                notes.append(note)
            revision.applied += 1
        revision.status = "diterapkan" if revision.applied else ALREADY_DONE if already else "gagal"
        if revision.ganti is None and revision.applied:
            revision.status = "komentar"
        revision.detail = "; ".join(dict.fromkeys(notes))

    for phase in (STRUCTURAL_OPERATIONS, [("format_nomor", op_number_format)]):
        for revision in revisions:
            if revision.target is None or revision.status not in ("", "diterapkan", ALREADY_DONE) or not revision.structural:
                continue
            if any(key in revision.extra for key, _ in phase):
                apply_structural(revision, workspace, with_comments, phase)

    report(revisions, arguments.laporan)
    config = json.loads(Path(arguments.tata_tulis).read_text(encoding="utf-8")) if arguments.tata_tulis else {}
    body = document_root.find(w("body"))
    if arguments.tata_tulis:
        counts = apply_layout_rules(body, config, styles, session)
        print("Tata tulis: " + ", ".join(f"{name} {count}" for name, count in counts.items()))
    if arguments.nomori_ulang or config.get("nomori_ulang"):
        print("Penomoran ulang judul tabel/gambar (lama → baru):")
        for line in renumber_captions(body, styles, session, config):
            print(line)
    if arguments.uji:
        print("\nMode uji: tidak ada file yang ditulis.")
        return 0

    package.flush()
    if session.comments:
        files[comments_name] = build_comments_part(existing_comments, session.comments, session)
        ensure_comments_wiring(files)

    written = set()
    with zipfile.ZipFile(output, "w", zipfile.ZIP_DEFLATED) as archive:
        for info in infos:
            if info.filename in files:
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
        mark = {"diterapkan": "OK ", "komentar": "KMT", ALREADY_DONE: "== "}.get(revision.status, "!! ")
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
    parser.add_argument("--tanpa-alasan", action="store_true", help="komentar hanya untuk butir manual")
    parser.add_argument("--pertama", action="store_true", help="bila ambigu, pakai kemunculan pertama")
    parser.add_argument("--hanya", help="id butir dipisah koma")
    parser.add_argument("--laporan", help="simpan laporan ke CSV")
    parser.add_argument("--tata-tulis", help="file .json aturan tata tulis (miring, judul tabel/gambar, spasi)")
    parser.add_argument("--nomori-ulang", action="store_true",
                        help="nomori ulang judul Tabel/Gambar per bab dan rujukannya setelah semua revisi")
    parser.add_argument("--uji", action="store_true", help="cek saja tanpa menulis file")
    return run(parser.parse_args())


if __name__ == "__main__":
    sys.exit(main())
