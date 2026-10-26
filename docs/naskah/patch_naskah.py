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
  --bersih F.docx   tulis juga salinan bersih: semua perubahan diterima, komentar
                    hanya dari butir bertanda "komentar_bersih": true
  --referensi-baru P  tulis P.ris dan P.bib berisi karya baru (yang belum ada di
                    pustaka Mendeley) untuk diimpor penulis ke Mendeley, dan
                    koreksi-pustaka-mendeley.bib (di folder P) berisi karya Mendeley yang
                    dikoreksi lewat "perbaikan" (kunci BibTeX dari "kunci"), agar penulis bisa
                    memperbarui pustakanya sehingga Refresh tidak memunculkan data lama

Pembangunan lengkap naskah skripsi (dari folder naskah.docx):
  python3 -I patch_naskah.py naskah.docx revisi-naskah.json --tata-tulis tata-tulis.json \
      --tanpa-alasan --penulis "Wan Salma Nurkhairin Fitri" --nomori-ulang \
      -o naskah-revisi-final.docx --bersih naskah-final-bersih.docx --referensi-baru referensi-baru

Kunci tata-tulis.json:
  istilah_asing        istilah asing yang dimiringkan (frasa panjang didahulukan); judul berbahasa
                       Inggris (dalam tanda petik, atau deret Title Case yang memuat kata tugas
                       Inggris), kata kapital semua di luar baris judul, field, daftar pustaka,
                       dan tabel/kolom yang dikecualikan tidak disentuh
  istilah_tegak        kata Indonesia/akronim yang harus tegak walau tercetak miring; judul artikel
                       dalam tanda petik juga ditegakkan (APA 7)
  kecualikan_tabel, kecualikan_kolom_tabel ({"2.1": ["Judul"]}), tegakkan_kolom_tabel
  kecualikan_gaya      gaya paragraf yang tidak dimiringkan dan spasinya tidak dirapikan
                       (mis. ["code"] untuk kotak JSON/kode)
  hapus_paragraf_kosong  hapus paragraf kosong yang terselip di antara dua paragraf isi
  perbarui_field       w:updateFields di settings.xml (Word memperbarui Daftar Isi/Tabel/Gambar)
  jangan_kompres_gambar  w:doNotAutoCompressPictures (Word tidak menurunkan resolusi gambar)
  seragamkan_tabel     {"contoh": "Tabel 3.27", "kecuali": ["Tabel 2.1"]}: setiap tabel berjudul "Tabel B.N"
                       mengikuti format tabel contoh sebagai Track Changes (tblPrChange, trPrChange,
                       tcPrChange, pPrChange, rPrChange): gaya tabel, tblBorders, tblCellMar, jc dan tblInd
                       tabel; tcBorders dan tcMar dihapus; arsiran dan vAlign sel mengikuti contoh; trHeight dan
                       jc baris dihapus; pStyle, spacing, ind, contextualSpacing paragraf sel dan ukuran huruf
                       (sz/szCs) mengikuti contoh; baris judul kolom (baris pertama yang seluruhnya tebal)
                       memakai perataan dan tblHeader contoh. Lebar kolom, sel gabung, isi, huruf tebal,
                       perataan teks isi, dan indentasi gantung butir bernomor ("1.", "2a.") tetap. Tabel
                       tanpa judul (kotak kode/rumus) tidak disentuh.
  pustaka              berkas data sitasi (pustaka.json): setiap sitasi di batang tubuh (paragraf,
                       sel tabel, judul) menjadi field Mendeley ADDIN CSL_CITATION berformat APA 7
                       (Track Changes: run field di w:ins, teks lama di w:del). Karya yang sudah ada
                       di field Mendeley naskah memakai itemData dan uris-nya; "perbaikan" membetulkan
                       data yang salah atau melengkapi yang kosong (nilai null menghapus kunci, "kunci" = kunci BibTeX
                       koreksi, "bukti" = URL sumber per kolom); "baru" berisi itemData CSL karya baru (tanpa uris). Sitasi
                       teks biasa "(Nama, 2020)", "Nama et al. (2020, hlm. 5)", "Sumber : Nama, 2020"
                       dan sel tabel berisi nama penulis (tahun di kolom lain) dikenali; lokator
                       halaman/tabel dihapus, "dan" untuk sitasi naratif dua penulis, beberapa karya
                       diurutkan abjad, huruf a/b/c dihitung otomatis, "t.t." untuk tanpa tahun.
                       Field CSL_BIBLIOGRAPHY disusun ulang dari itemData karya yang disitasi saja
                       (urut abjad, DOI https://doi.org/..., judul sentence case, nama jurnal title
                       case dan miring bersama volume; judul artikel/makalah prosiding tidak pernah
                       miring; "number" artikel tanpa halaman ditulis "Artikel N").

Format JSON:
  {"revisi": [{"id": "2.9-01", "lokasi": "2.9 hlm. 30",
               "cari": "awal kalimat…akhir kalimat", "ganti": "teks baru",
               "catatan": "instruksi manual", "semua": false}]}
  - "…" (elipsis) di "cari" berarti teks apa saja di paragraf yang sama.
  - *teks* di "ganti" ditulis miring; \\* untuk tanda bintang biasa.
  - Suntingan yang memotong field sitasi (mis. menghapus satu karya dari
    "(A, 2020; B, 2021)") memperluas diri sehingga field lama dihapus utuh;
    sitasi dalam teks pengganti dijadikan field Mendeley lagi oleh tahap sitasi.
  - "semua": true mengganti setiap kemunculan (untuk istilah).
  - "urutan": n memakai kemunculan ke-n (dihitung pada naskah asli) bila
    "cari" ditemukan di beberapa tempat.
  - "utuh": true hanya cocok bila "cari" mencakup seluruh isi paragraf
    (mis. satu sel tabel berisi "Dashboard" saja).
  - "tegak": true teks pengganti tidak ikut miring walaupun teks lama miring.
  - "tetap_komentar": true komentar tetap dipasang walaupun --tanpa-alasan
    (untuk hal yang masih harus dikerjakan atau diputuskan penulis).
  - "komentar_bersih": true komentar ikut ke salinan bersih (--bersih); komentar
    lain (alasan revisi, "Gambar diganti ...") hanya ada di berkas Track Changes.

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
      {"teks": "...", "gaya": "Heading3", "nomor": "2.8.%1"}
                                                   judul bernomor otomatis dengan format
                                                   sendiri (daftar baru meniru judul bernomor
                                                   bergaya sama; format sama = daftar sama)
    "pindah_pemisah_halaman": true (pada butir) memindahkan pemisah halaman di akhir
    paragraf "cari" ke akhir sisipan (terlacak), untuk sisipan di akhir bab.
      {"teks": "...", "seperti": "<cari>"}         salin pPr paragraf itu
      {"tabel": [["sel", ...], ...], "seperti": "<judul atau isi tabel contoh>",
       "lebar": [1, 4, 5], "rata": "kiri", "rata_sel": "kiri"}
                                                   tabel baru (baris tersisip),
                                                   format dari tabel contoh; "rata_sel":
                                                   teks sel isi rata kiri, bukan rata
                                                   kanan-kiri; "\n" di sel = paragraf baru
      {"gambar": "berkas.png", "lebar_cm": 14}     paragraf gambar baru; bentuk dan
                                                   lebar dari gambar terdekat, atau
                                                   "lebar_cm" (tinggi ikut rasio,
                                                   maks. 80% tinggi bidang teks)
      {"teks": "BAB IV\nJUDUL", "gaya": "Heading1", "halaman_baru": true}
                                                   "\n" di teks menjadi pindah baris
                                                   (w:br); "halaman_baru" memberi
                                                   pageBreakBefore (bab baru)
    Tabel baru boleh berjumlah kolom lain dari tabel contoh: sel dibuang dari kanan
    atau sel terakhir disalin, sel gabung dilepas, lebar dibagi rata (atur dengan "lebar").
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
    "tanpa_penanda": false, "judul_otomatis": false, "penanda": [...],
    "tanpa_paragraf_kosong": false}
    salin bagian dokumen lain (dari paragraf yang diawali penanda sampai
    penanda berikutnya milik berkas yang sama, atau akhir dokumen) sesudah
    paragraf "cari", sebagai Track Changes. Gaya dipetakan ke gaya naskah (id
    atau nama sama; bila tidak ada, definisinya disalin), daftar bernomor
    disalin dengan id baru, gambar disalin dengan rId dan docPr id baru, judul
    tabel/gambar memakai gaya judul naskah. "judul_otomatis": judul bernomor
    ketikan ("3.7.3 Skenario") ikut penomoran otomatis judul setingkat di
    subbab yang sama bila ada. "tanpa_paragraf_kosong": paragraf kosong (jarak
    ketikan) tidak ikut disalin, kecuali pemisah dua tabel.
  - "ganti_gambar": "berkas.png" ganti gambar di atas judul "cari": lebar tampil
    tetap (atau "lebar_cm": 14.5), tinggi mengikuti rasio baru (maks. 80% tinggi halaman). Word tidak
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
import bisect
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
    clean_comment_ids: set[int] = field(default_factory=set)
    cited_works: list = field(default_factory=list)
    library_config: dict = field(default_factory=dict)

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
        elif char == "\n":
            flush()
            elements.append(etree.Element(w("br")))
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


def attach_comment(first: etree._Element, last: etree._Element, text: str, session: Session,
                   keep_in_clean: bool = False) -> None:
    comment_id = str(session.next_comment_id)
    if keep_in_clean:
        session.clean_comment_ids.add(int(comment_id))
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


def widen_edits_to_fields(edits: list[tuple[int, int, int, int]], fields: list[tuple[int, int]],
                          positions: list[int]) -> list[tuple[int, int, int, int]]:
    """Perluas suntingan yang memotong field (sitasi) sehingga field diganti utuh.

    Rentang lama dan baru diperluas sama panjang karena teks di luar suntingan identik;
    suntingan yang kemudian bertumpang tindih digabung. Sitasi baru dalam teks pengganti
    dijadikan field lagi oleh tahap sitasi.
    """
    plain_fields = [(bisect.bisect_left(positions, start), bisect.bisect_left(positions, end)) for start, end in fields]
    plain_fields = [(start, end) for start, end in plain_fields if start < end]
    if not plain_fields or not edits:
        return edits

    def new_position(position: int, include_insertion: bool) -> int:
        shift = 0
        for old_from, old_to, new_from, new_to in edits:
            pure_insertion = old_from == old_to
            if old_to < position or (old_to == position and (not pure_insertion or include_insertion)):
                shift += (new_to - new_from) - (old_to - old_from)
        return position + shift

    ranges = [[old_from, old_to] for old_from, old_to, _, _ in edits]
    for item in ranges:
        for start, end in plain_fields:
            if item[0] == item[1]:
                cuts = start < item[0] < end
            else:
                cuts = start < item[1] and end > item[0] and not (item[0] <= start and end <= item[1])
            if cuts:
                item[0], item[1] = min(item[0], start), max(item[1], end)
    ranges.sort()
    merged: list[list[int]] = []
    for item in ranges:
        touching = merged and item[0] == merged[-1][1] and (item[0] == item[1] or merged[-1][0] == merged[-1][1])
        if merged and (item[0] < merged[-1][1] or touching):
            merged[-1][1] = max(merged[-1][1], item[1])
        else:
            merged.append(list(item))
    ranges = merged
    if [tuple(item) for item in ranges] == [(old_from, old_to) for old_from, old_to, _, _ in edits]:
        return edits
    return [(old_from, old_to, new_position(old_from, False), new_position(old_to, True)) for old_from, old_to in ranges]


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
        fields = [(field_start, field_end) for field_start, field_end, _ in field_spans(segments)[0]
                  if field_end > match.start and field_start < match.end
                  and all(text[index] in (FIELD, BARRIER) or match.start <= index < match.end
                          for index in range(field_start, field_end))]
        edits = widen_edits_to_fields(word_edits(old_key, new_key), fields, positions)

        def raw_range(old_from: int, old_to: int) -> tuple[int, int]:
            start = positions[old_from] if old_from < len(positions) else match.end
            end = positions[old_to - 1] + 1 if old_to > old_from else start
            for field_start, field_end in fields:
                if start == end and start in positions and bisect.bisect_left(positions, field_start) == old_from \
                        and field_start < start < field_end:
                    return field_start, field_start
                if start < end and field_start < end and field_end > start:
                    start, end = min(start, field_start), max(end, field_end)
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
            attach_comment(first, last, f"[{revision.id}] {revision.catatan}", session,
                           keep_in_clean=bool(revision.extra.get("komentar_bersih")))
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


def set_alignment(paragraph: etree._Element, value: str, session: Session) -> bool:
    """Ubah perataan paragraf (w:jc) sebagai Track Changes (pPrChange); False bila sudah sama."""
    properties = paragraph.find(w("pPr"))
    if properties is None:
        properties = etree.Element(w("pPr"))
        paragraph.insert(0, properties)
    current = properties.find(w("jc"))
    if current is not None and current.get(w("val")) == value:
        return False
    change = None
    if session.tracked and not paragraph_inserted(paragraph) and properties.find(w("pPrChange")) is None:
        previous = etree.Element(w("pPr"))
        for child in properties:
            if child.tag not in (w("rPr"), w("sectPr"), w("pPrChange")):
                previous.append(deepcopy(child))
        change = etree.Element(w("pPrChange"), revision_attributes(session))
        change.append(previous)
    if current is not None:
        properties.remove(current)
    place_in_order(properties, etree.Element(w("jc"), {w("val"): value}), PARAGRAPH_PROPERTY_ORDER)
    if change is not None:
        place_in_order(properties, change, PARAGRAPH_PROPERTY_ORDER)
    return True


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
    columns = {str(number): set(headers) for number, headers in config.get("kecualikan_kolom_tabel", {}).items()}
    styles = set(config.get("kecualikan_gaya", []))
    if styles:
        for paragraph in body.iter(w("p")):
            style = paragraph.find(f"{w('pPr')}/{w('pStyle')}")
            if style is not None and style.get(w("val")) in styles:
                excluded.add(paragraph)
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
            for number, headers in columns.items():
                if re.match(rf"Tabel {re.escape(number)}\b", caption):
                    excluded.update(column_paragraphs(child, headers))
    return excluded


def column_paragraphs(table: etree._Element, headers: set[str]) -> list[etree._Element]:
    """Paragraf di kolom tabel yang judul kolomnya (baris pertama) ada di headers."""
    rows = table.findall(w("tr"))
    if not rows:
        return []
    wanted = {index for index, cell in enumerate(rows[0].findall(w("tc")))
              if paragraph_plain_text(cell).strip() in headers}
    found = []
    for row in rows:
        for index, cell in enumerate(row.findall(w("tc"))):
            if index in wanted:
                found.extend(cell.iter(w("p")))
    return found


ENGLISH_FUNCTION_WORDS = {"of", "in", "and", "the", "for", "with", "on", "a", "an", "to", "using", "based", "at", "by",
                          "from", "between", "into", "via"}


def quoted_span(text: str, start: int, end: int) -> tuple[int, int] | None:
    """Rentang isi tanda petik (“…” atau "…") yang memuat [start, end), bila ada."""
    for opening, closing in (("“", "”"), ('"', '"')):
        left = text.rfind(opening, 0, start)
        if left < 0:
            continue
        right = text.find(closing, end)
        if right < 0 or (opening == closing and text.count(opening, 0, left) % 2):
            continue
        if closing not in text[left + 1:start]:
            return left + 1, right
    return None


def is_english_title(segment: str) -> bool:
    """Judul berbahasa Inggris: paling sedikit dua kata tugas Inggris (of, the, in, ...) dan tiga kata berhuruf besar."""
    words = re.findall(r"[A-Za-z][\w'’-]*", segment)
    return sum(word.lower() in ENGLISH_FUNCTION_WORDS for word in words) >= 2 and sum(word[0].isupper() for word in words) >= 3


def in_english_title(text: str, start: int, end: int) -> bool:
    """True bila istilah berada di judul berbahasa Inggris: judul dalam tanda petik, atau deret Title Case.

    Deret Title Case (≥ 4 kata berhuruf besar) hanya dianggap judul bila memuat kata tugas Inggris di luar
    istilah itu sendiri, jadi judul gambar/subbab berbahasa Indonesia ("Wireframe Halaman Beranda") dan
    deret nama lapisan ("IoT Layer, Processing Layer") tetap dimiringkan.
    """
    quoted = quoted_span(text, start, end)
    if quoted is not None:
        return is_english_title(text[quoted[0]:quoted[1]])
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
    function_words = sum(1 for token in tokens[left:right + 1]
                         if token.group().lower() in ENGLISH_FUNCTION_WORDS and not start <= token.start() < end)
    return capitalised >= 4 and function_words >= 1


def capitalised_code(text: str, start: int, end: int) -> bool:
    """Kata kapital semua (mis. tipe data TIMESTAMP) tidak dimiringkan, kecuali di baris judul kapital semua."""
    matched = text[start:end]
    if not (matched.isupper() and len(matched) > 3):
        return False
    line = text.replace(FIELD, "").replace(BARRIER, "").strip()
    return not (line == line.upper() and len(line.split()) >= 3)


def italicise_terms(body: etree._Element, config: dict, styles: StyleBook, session: Session) -> int:
    terms = sorted(config.get("istilah_asing", []), key=len, reverse=True)
    if not terms:
        return 0
    # Spasi lebar-nol (U+200B, titik pemenggalan pada nama kode panjang) dianggap bagian kata, dan istilah yang
    # menjadi bagian nama berkas/produk bertitik (Chart.js, web.php) tidak dimiringkan.
    pattern = re.compile(r"(?<![\w\u200b./-])(" + "|".join(re.escape(term) for term in terms)
                         + r")(?![\w\u200b-]|[./]\w)", re.IGNORECASE)
    excluded = excluded_paragraphs(body, config)
    changed = 0
    for paragraph in list(body.iter(w("p"))):
        if paragraph in excluded or is_listing_paragraph(paragraph):
            continue
        segments, text = paragraph_model(paragraph)
        _, inside_field = field_spans(segments)
        flags = []
        for segment in segments:
            italic = segment.kind == "text" and styles.is_italic(segment.run, paragraph)
            flags.extend([italic] * len(segment.chars))
        targets = [match for match in pattern.finditer(text)
                   if not all(flags[match.start():match.end()]) and not any(inside_field[match.start():match.end()])
                   and not in_english_title(text, match.start(), match.end())
                   and not capitalised_code(text, match.start(), match.end())]
        for match in reversed(targets):
            segments, _ = paragraph_model(paragraph)
            for run in isolate(segments, match.start(), match.end()):
                if run.tag == w("r") and run_kind(run)[0] == "text" and not styles.is_italic(run, paragraph):
                    set_italic(run, session)
            changed += 1
    return changed


def set_upright(run: etree._Element, styles: StyleBook, paragraph: etree._Element, session: Session) -> None:
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
    for tag in ("i", "iCs"):
        for existing in properties.findall(w(tag)):
            properties.remove(existing)
    for style in properties.findall(w("rStyle")):
        if style.get(w("val")) in ITALIC_CHARACTER_STYLES:
            properties.remove(style)
    if styles.is_italic(run, paragraph):
        place_in_order(properties, etree.Element(w("i"), {w("val"): "0"}), RUN_PROPERTY_ORDER)
    if change is not None:
        place_in_order(properties, change, RUN_PROPERTY_ORDER)
    if not len(properties):
        run.remove(properties)


def upright_terms(body: etree._Element, config: dict, styles: StyleBook, session: Session) -> int:
    """Tegakkan kata Indonesia/akronim yang tercetak miring dan judul artikel berbahasa Inggris dalam tanda petik.

    "istilah_tegak": akronim (huruf besar semua atau berhuruf besar di tengah) selalu ditegakkan; kata lain
    hanya bila miringnya berdiri sendiri (bukan bagian frasa asing yang miring). Judul artikel dalam tanda
    petik ditulis tegak (APA 7); istilah asing di judul berbahasa Indonesia dimiringkan lagi oleh
    italicise_terms.
    """
    terms = sorted(config.get("istilah_tegak", []), key=len, reverse=True)
    pattern = re.compile(r"(?<![\w-])(" + "|".join(re.escape(term) for term in terms) + r")(?![\w-])") if terms else None
    excluded = excluded_paragraphs(body, config)
    changed = 0
    upright_cells = excluded_paragraphs(body, {"kecualikan_setelah_judul": "\x00", "kecualikan_kolom_tabel":
                                               config.get("tegakkan_kolom_tabel", {})})
    for paragraph in upright_cells:
        segments, _ = paragraph_model(paragraph)
        _, inside_field = field_spans(segments)
        for segment in segments:
            if segment.kind == "text" and not any(inside_field[segment.start:segment.end]) \
                    and styles.is_italic(segment.run, paragraph):
                set_upright(segment.run, styles, paragraph, session)
                changed += 1
    for paragraph in list(body.iter(w("p"))):
        if paragraph in excluded or is_listing_paragraph(paragraph):
            continue
        segments, text = paragraph_model(paragraph)
        _, inside_field = field_spans(segments)
        flags = []
        for segment in segments:
            flags.extend([segment.kind == "text" and styles.is_italic(segment.run, paragraph)] * len(segment.chars))
        spans: list[tuple[int, int]] = []
        for quote in re.finditer(r"“([^”]+)”", text):
            body_start, body_end = quote.span(1)
            if any(flags[body_start:body_end]) and len(re.findall(r"\b[A-Z]", quote.group(1))) >= 4:
                spans.append((body_start, body_end))
        if pattern is not None:
            for found in pattern.finditer(text):
                start, end = found.span()
                term = found.group()
                partial_ok = "." in term and any(flags[start:end])
                if not (all(flags[start:end]) or partial_ok) or any(inside_field[start:end]):
                    continue
                acronym = term.isupper() or any(char.isupper() for char in term[1:])
                left, right = start, end
                while left > 0 and flags[left - 1]:
                    left -= 1
                while right < len(text) and flags[right]:
                    right += 1
                alone = not re.search(r"[A-Za-z]", text[left:start] + text[end:right])
                if acronym or alone:
                    spans.append((start, end))
        for start, end in sorted(set(spans), reverse=True):
            segments, _ = paragraph_model(paragraph)
            for run in isolate(segments, start, end):
                if run.tag == w("r") and run_kind(run)[0] == "text" and styles.is_italic(run, paragraph):
                    set_upright(run, styles, paragraph, session)
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


def tidy_spaces(body: etree._Element, session: Session, skip_styles: set[str] | None = None) -> int:
    changed = 0
    for paragraph in list(body.iter(w("p"))):
        if is_listing_paragraph(paragraph):
            continue
        style = paragraph.find(f"{w('pPr')}/{w('pStyle')}")
        if skip_styles and style is not None and style.get(w("val")) in skip_styles:
            continue
        _, text = paragraph_model(paragraph)
        if "{" in text or "\t" in text.strip():
            continue
        spans = set()
        for rule in SPACE_RULES:
            for match in rule.finditer(text):
                spans.add((match.start("hapus"), match.end("hapus")))
        trailing = re.search(r"(?<=[^\s\x00\x01])[ \t]*\t[ \t]*$", text)
        if trailing:
            spans.add((trailing.start(), trailing.end()))
        for start, end in sorted(spans, reverse=True):
            segments, current = paragraph_model(paragraph)
            if current[start:end].strip(" \t"):
                continue
            delete_runs(isolate(segments, start, end), session)
            changed += 1
    return changed


def remove_stray_empty_paragraphs(body: etree._Element, config: dict, styles: StyleBook, session: Session) -> int:
    """Hapus paragraf kosong yang terselip di antara dua paragraf isi (bukan judul, judul tabel/gambar, atau gambar).

    Hanya di batang tubuh: sesudah judul bab pertama ("BAB I") dan sebelum daftar pustaka; halaman judul,
    jarak sebelum judul subbab, dan jarak sesudah gambar tidak disentuh.
    """
    start_text = config.get("isi_mulai", "BAB I").upper()
    end_text = config.get("kecualikan_setelah_judul", "DAFTAR PUSTAKA").upper()
    children = list(body)
    inside = False
    changed = 0

    def is_text_paragraph(element: etree._Element | None) -> bool:
        if element is None or element.tag != w("p") or has_drawing(element) or is_listing_paragraph(element):
            return False
        text = live_text(element).strip()
        style = (styles.paragraph_style(element) or "").lower()
        return bool(text) and not style.startswith("heading") and not CAPTION_RE.match(text) \
            and element.find(f"{w('pPr')}/{w('numPr')}") is None

    for index, child in enumerate(children):
        if child.tag != w("p"):
            continue
        text = live_text(child).strip().upper()
        if text.replace(" ", "").startswith(start_text.replace(" ", "")) and not is_listing_paragraph(child):
            inside = True
        if text == end_text and not is_listing_paragraph(child):
            break
        if not inside or text or has_drawing(child) or paragraph_deleted(child) or child.find(f".//{w('sectPr')}") is not None \
                or child.find(f".//{w('br')}") is not None or child.find(f".//{w('fldChar')}") is not None:
            continue
        if (styles.paragraph_style(child) or "") != (styles.default_paragraph or ""):
            continue
        before = children[index - 1] if index else None
        after = children[index + 1] if index + 1 < len(children) else None
        if is_text_paragraph(before) and is_text_paragraph(after):
            delete_paragraph(child, session)
            changed += 1
    return changed


def has_inserted_mark(paragraph: etree._Element) -> bool:
    return paragraph.find(f"{w('pPr')}/{w('rPr')}/{w('ins')}") is not None


def keep_headings_with_next(body: etree._Element, styles: StyleBook, session: Session) -> int:
    """Heading tidak boleh tertinggal sendirian di dasar halaman."""
    changed = 0
    for paragraph in body.iter(w("p")):
        style = styles.paragraph_style(paragraph) or ""
        if not re.fullmatch(r"(?i)heading[1-6]", style) or is_listing_paragraph(paragraph):
            continue
        if not paragraph_plain_text(paragraph).strip() or styles.keeps_with_next(paragraph):
            continue
        if has_inserted_mark(paragraph):
            properties = paragraph.find(w("pPr"))
            place_in_order(properties, etree.Element(w("keepNext")), PARAGRAPH_PROPERTY_ORDER)
        else:
            set_keep_next(paragraph, session)
        changed += 1
    return changed


def align_body_cells_left(table: etree._Element) -> None:
    """Paragraf sel baris isi (selain baris pertama) yang rata kanan-kiri atau tanpa perataan dibuat rata kiri.

    Sel berperataan tengah/kanan (mis. kolom No) tetap; dipakai untuk tabel sisipan berisi teks panjang.
    """
    for row in table.findall(w("tr"))[1:]:
        for paragraph in row.iter(w("p")):
            properties = paragraph.find(w("pPr"))
            if properties is None:
                properties = etree.Element(w("pPr"))
                paragraph.insert(0, properties)
            alignment = properties.find(w("jc"))
            if alignment is None:
                alignment = etree.Element(w("jc"))
                place_in_order(properties, alignment, PARAGRAPH_PROPERTY_ORDER)
            if alignment.get(w("val")) in (None, "both", "distribute"):
                alignment.set(w("val"), "left")


def align_table_left(table: etree._Element) -> None:
    """Isi sel tabel sisipan rata kiri, agar tidak muncul celah lebar antarkata."""
    for paragraph in table.iter(w("p")):
        properties = paragraph.find(w("pPr"))
        if properties is None:
            properties = etree.Element(w("pPr"))
            paragraph.insert(0, properties)
        for existing in properties.findall(w("jc")):
            properties.remove(existing)
        place_in_order(properties, etree.Element(w("jc"), {w("val"): "left"}), PARAGRAPH_PROPERTY_ORDER)


def apply_layout_rules(body: etree._Element, config: dict, styles: StyleBook, session: Session) -> dict[str, int]:
    counts = {}
    if config.get("hapus_paragraf_kosong"):
        counts["paragraf kosong di antara paragraf isi dihapus"] = remove_stray_empty_paragraphs(body, config, styles, session)
    if config.get("jaga_judul", True):
        counts["judul tabel/gambar dijaga"] = keep_captions_with_objects(body, styles, session)
    if config.get("jaga_heading", True):
        counts["heading dijaga bersama paragraf berikutnya"] = keep_headings_with_next(body, styles, session)
    counts["kata Indonesia/akronim/judul kutipan ditegakkan"] = upright_terms(body, config, styles, session)
    counts["istilah asing dimiringkan"] = italicise_terms(body, config, styles, session)
    if config.get("rapikan_spasi", True):
        counts["spasi dirapikan"] = tidy_spaces(body, session, set(config.get("kecualikan_gaya", [])))
    return counts


# ---------------------------------------------------------------------------
# seragamkan_tabel: format semua tabel berjudul mengikuti satu tabel contoh.
# ---------------------------------------------------------------------------

TABLE_PROPERTY_ORDER = [
    "tblStyle", "tblpPr", "tblOverlap", "bidiVisual", "tblStyleRowBandSize", "tblStyleColBandSize", "tblW", "jc",
    "tblCellSpacing", "tblInd", "tblBorders", "shd", "tblLayout", "tblCellMar", "tblLook", "tblCaption",
    "tblDescription", "tblPrChange",
]
ROW_PROPERTY_ORDER = [
    "cnfStyle", "divId", "gridBefore", "gridAfter", "wBefore", "wAfter", "cantSplit", "trHeight", "tblHeader",
    "tblCellSpacing", "jc", "hidden", "ins", "del", "trPrChange",
]
CELL_PROPERTY_ORDER = [
    "cnfStyle", "tcW", "gridSpan", "hMerge", "vMerge", "tcBorders", "shd", "noWrap", "tcMar", "textDirection",
    "tcFitText", "vAlign", "hideMark", "headers", "cellIns", "cellDel", "cellMerge", "tcPrChange",
]
MARK_PROPERTY_ORDER = ["ins", "del", "moveFrom", "moveTo"] + RUN_PROPERTY_ORDER
TABLE_PARAGRAPH_PROPERTIES = ("pStyle", "spacing", "ind", "contextualSpacing")
ENUMERATION_RE = re.compile(r"\s*(?:\d+[a-z]?\.|[a-z]\.|[-•–])\s")


def tracked_properties(properties: etree._Element, change: str, exclude: tuple[str, ...], order: list[str],
                       session: Session, mutate, track: bool = True) -> bool:
    """Ubah elemen properti (tblPr/trPr/tcPr/pPr/rPr) lewat mutate(); simpan versi lama sebagai *PrChange.

    Bila *PrChange sudah ada (perubahan format sebelumnya), versi asli di dalamnya dipertahankan.
    """
    before = etree.tostring(properties)
    previous = [deepcopy(child) for child in properties if child.tag not in {w(tag) for tag in exclude} | {w(change)}]
    mutate(properties)
    if etree.tostring(properties) == before:
        return False
    if track and session.tracked and properties.find(w(change)) is None:
        inner_tag = etree.QName(properties).localname
        record = etree.Element(w(change), revision_attributes(session))
        inner = etree.SubElement(record, w(inner_tag))
        inner.extend(previous)
        place_in_order(properties, record, order)
    return True


def set_child(properties: etree._Element, name: str, value: etree._Element | None, order: list[str]) -> None:
    """Ganti anak bernama name dengan salinan value (None: hapus)."""
    for existing in properties.findall(w(name)):
        properties.remove(existing)
    if value is not None:
        place_in_order(properties, deepcopy(value), order)


def ensure_properties(element: etree._Element, name: str) -> etree._Element:
    properties = element.find(w(name))
    if properties is None:
        properties = etree.Element(w(name))
        element.insert(0, properties)
    return properties


def captioned_tables(body: etree._Element) -> list[tuple[str, etree._Element]]:
    """(nomor, tabel) untuk setiap tabel tingkat atas yang didahului judul "Tabel B.N" (paragraf kosong dilewati)."""
    found = []
    children = list(body)
    for index, child in enumerate(children):
        if child.tag != w("tbl"):
            continue
        previous = index - 1
        while previous >= 0 and children[previous].tag == w("p") and not paragraph_plain_text(children[previous]).strip():
            previous -= 1
        if previous < 0 or children[previous].tag != w("p"):
            continue
        match = re.match(r"\s*Tabel\s+(\d+\.\d+)\b", paragraph_plain_text(children[previous]))
        if match:
            found.append((match.group(1), child))
    return found


def header_row(table: etree._Element) -> etree._Element | None:
    """Baris pertama bila semua teksnya tebal (baris judul kolom), selain itu None."""
    rows = table.findall(w("tr"))
    if not rows:
        return None
    runs = [run for run in rows[0].iter(w("r")) if "".join(t.text or "" for t in run.iter(w("t"))).strip()]
    if runs and all(is_on(run.find(f"{w('rPr')}/{w('b')}")) for run in runs):
        return rows[0]
    return None


def consistent(values: list[bytes | None]):
    """Satu-satunya nilai bila semua sama, selain itu tanda tidak konsisten (...)."""
    distinct = set(values)
    return distinct.pop() if len(distinct) == 1 else ...


def table_model(table: etree._Element) -> dict:
    """Ambil format tabel contoh: properti tabel, sel, paragraf isi/judul kolom, dan ukuran huruf."""
    properties = ensure_properties(table, "tblPr")
    model = {"table": {name: properties.find(w(name)) for name in ("tblStyle", "tblBorders", "tblCellMar", "jc", "tblInd")}}
    header = header_row(table)
    model["header"] = header is not None
    cells = [cell for row in table.findall(w("tr")) for cell in row.findall(w("tc"))]

    def serial(element: etree._Element | None) -> bytes | None:
        return etree.tostring(element) if element is not None else None

    model["vAlign"] = consistent([serial(cell.find(f"{w('tcPr')}/{w('vAlign')}")) for cell in cells])
    header_cells = header.findall(w("tc")) if header is not None else []
    body_cells = [cell for cell in cells if cell not in header_cells]
    model["shd"] = consistent([serial(cell.find(f"{w('tcPr')}/{w('shd')}")) for cell in body_cells]) if body_cells else None
    model["header_shd"] = (consistent([serial(cell.find(f"{w('tcPr')}/{w('shd')}")) for cell in header_cells])
                           if header_cells else model["shd"])
    body_paragraphs_ = [paragraph for row in table.findall(w("tr")) if row is not header for paragraph in row.iter(w("p"))]
    header_paragraphs = list(header.iter(w("p"))) if header is not None else []
    model["paragraph"] = {name: consistent([serial(paragraph.find(f"{w('pPr')}/{w(name)}")) for paragraph in body_paragraphs_ + header_paragraphs])
                          for name in TABLE_PARAGRAPH_PROPERTIES}
    model["header_jc"] = consistent([serial(paragraph.find(f"{w('pPr')}/{w('jc')}")) for paragraph in header_paragraphs]) if header_paragraphs else ...
    model["header_row"] = header.find(f"{w('trPr')}/{w('tblHeader')}") if header is not None else None
    sizes = [(serial(run.find(f"{w('rPr')}/{w('sz')}")), serial(run.find(f"{w('rPr')}/{w('szCs')}"))) for run in table.iter(w("r"))
             if run.find(w("t")) is not None]
    model["size"] = consistent(sizes) if sizes else ...
    return model


def unify_table(table: etree._Element, model: dict, session: Session) -> bool:
    """Terapkan format tabel contoh ke satu tabel; lebar kolom, sel gabung, isi, dan perataan isi tetap."""
    changed = False

    def parse(value: bytes | None) -> etree._Element | None:
        return etree.fromstring(value) if value is not None else None

    def table_mutation(properties: etree._Element) -> None:
        for name, value in model["table"].items():
            set_child(properties, name, value, TABLE_PROPERTY_ORDER)

    changed |= tracked_properties(ensure_properties(table, "tblPr"), "tblPrChange", (), TABLE_PROPERTY_ORDER,
                                  session, table_mutation)
    header = header_row(table) if model["header"] else None
    for row in table.findall(w("tr")):
        row_properties = row.find(w("trPr"))
        inserted_row = row_properties is not None and row_properties.find(w("ins")) is not None

        def row_mutation(properties: etree._Element, is_header: bool = row is header) -> None:
            set_child(properties, "trHeight", None, ROW_PROPERTY_ORDER)
            set_child(properties, "jc", None, ROW_PROPERTY_ORDER)
            if is_header and model["header_row"] is not None:
                set_child(properties, "tblHeader", model["header_row"], ROW_PROPERTY_ORDER)

        if row_properties is not None or (row is header and model["header_row"] is not None):
            properties = row_properties if row_properties is not None else etree.Element(w("trPr"))
            if row_properties is None:
                row.insert(0 if row.find(w("tblPrEx")) is None else 1, properties)
            changed |= tracked_properties(properties, "trPrChange", ("ins", "del"), ROW_PROPERTY_ORDER, session,
                                          row_mutation, track=not inserted_row)
            if not len(properties):
                row.remove(properties)
        for cell in row.findall(w("tc")):

            def cell_mutation(properties: etree._Element, is_header: bool = row is header) -> None:
                for name in ("tcBorders", "tcMar"):
                    set_child(properties, name, None, CELL_PROPERTY_ORDER)
                shading = model["header_shd"] if is_header else model["shd"]
                if shading is not ...:
                    set_child(properties, "shd", parse(shading), CELL_PROPERTY_ORDER)
                if model["vAlign"] is not ...:
                    set_child(properties, "vAlign", parse(model["vAlign"]), CELL_PROPERTY_ORDER)

            changed |= tracked_properties(ensure_properties(cell, "tcPr"), "tcPrChange", ("cellIns", "cellDel", "cellMerge"),
                                          CELL_PROPERTY_ORDER, session, cell_mutation, track=not inserted_row)
            for paragraph in cell.findall(w("p")):
                changed |= unify_cell_paragraph(paragraph, model, row is header, session, inserted_row)
    return changed


def unify_cell_paragraph(paragraph: etree._Element, model: dict, in_header: bool, session: Session,
                         inserted_row: bool) -> bool:
    changed = False
    keep_hanging = bool(ENUMERATION_RE.match(paragraph_plain_text(paragraph)))

    def paragraph_mutation(properties: etree._Element) -> None:
        for name, value in model["paragraph"].items():
            if value is ...:
                continue
            if name == "ind" and keep_hanging:
                current = properties.find(w("ind"))
                if current is not None and current.get(w("hanging")):
                    continue
            set_child(properties, name, etree.fromstring(value) if value is not None else None, PARAGRAPH_PROPERTY_ORDER)
        if in_header and model["header_jc"] is not ...:
            value = model["header_jc"]
            set_child(properties, "jc", etree.fromstring(value) if value is not None else None, PARAGRAPH_PROPERTY_ORDER)

    inserted = inserted_row or paragraph_inserted(paragraph)
    changed |= tracked_properties(ensure_properties(paragraph, "pPr"), "pPrChange", ("rPr", "sectPr"),
                                  PARAGRAPH_PROPERTY_ORDER, session, paragraph_mutation, track=not inserted)
    if model["size"] is ...:
        return changed
    size, size_cs = (etree.fromstring(value) if value is not None else None for value in model["size"])

    def run_mutation(properties: etree._Element) -> None:
        set_child(properties, "sz", size, RUN_PROPERTY_ORDER)
        set_child(properties, "szCs", size_cs, RUN_PROPERTY_ORDER)

    for run in paragraph.iter(w("r")):
        if any(ancestor.tag == w("p") and ancestor is not paragraph for ancestor in run.iterancestors()):
            continue
        properties = ensure_properties(run, "rPr")
        changed |= tracked_properties(properties, "rPrChange", (), RUN_PROPERTY_ORDER, session, run_mutation,
                                      track=not (inserted or inside_insertion(run)))
        if not len(properties):
            run.remove(properties)
    paragraph_properties = ensure_properties(paragraph, "pPr")
    mark = paragraph_properties.find(w("rPr"))
    if mark is None:
        mark = etree.Element(w("rPr"))
        place_in_order(paragraph_properties, mark, PARAGRAPH_PROPERTY_ORDER)
    mark_tracked = mark.find(w("ins")) is None and mark.find(w("del")) is None and not inserted

    def mark_mutation(properties: etree._Element) -> None:
        set_child(properties, "sz", size, MARK_PROPERTY_ORDER)
        set_child(properties, "szCs", size_cs, MARK_PROPERTY_ORDER)

    changed |= tracked_properties(mark, "rPrChange", ("ins", "del", "moveFrom", "moveTo"), MARK_PROPERTY_ORDER, session,
                                  mark_mutation, track=mark_tracked)
    if not len(mark):
        paragraph_properties.remove(mark)
    return changed


def unify_tables(body: etree._Element, config: dict, session: Session) -> list[str]:
    """seragamkan_tabel: {"contoh": "Tabel 3.27", "kecuali": ["Tabel 2.1"]}."""
    settings = config.get("seragamkan_tabel") or {}
    wanted = re.sub(r"^\s*Tabel\s+", "", str(settings.get("contoh", "")))
    skipped = {re.sub(r"^\s*Tabel\s+", "", str(item)) for item in settings.get("kecuali", [])}
    tables = captioned_tables(body)
    examples = [table for number, table in tables if number == wanted]
    if not examples:
        return [f"  tabel contoh {settings.get('contoh')!r} tidak ditemukan; tabel tidak diubah"]
    model = table_model(examples[0])
    changed = [number for number, table in tables
               if table is not examples[0] and number not in skipped and unify_table(table, model, session)]
    return [f"  contoh: Tabel {wanted}; tabel diseragamkan ({len(changed)}): {', '.join(changed) or '-'}"]


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


def reshaped_template(template: etree._Element, columns: int) -> etree._Element:
    """Salinan tabel contoh (baris judul dan baris isi pertama) dengan jumlah kolom lain.

    Sel kelebihan dibuang dari kanan; sel kurang ditambah dengan menyalin sel terakhir. Sel gabung
    (gridSpan/vMerge) dilepas dan lebar total tabel dibagi rata ke kolom baru (atur ulang dengan "lebar").
    """
    copy = deepcopy(template)
    grid = copy.find(w("tblGrid"))
    old_columns = grid.findall(w("gridCol")) if grid is not None else []
    total = sum(int(column.get(w("w"), "0")) for column in old_columns) or 7927
    for row in copy.findall(w("tr"))[2:]:
        copy.remove(row)
    for row in copy.findall(w("tr")):
        cells = row.findall(w("tc"))
        for cell in cells:
            properties = cell.find(w("tcPr"))
            if properties is not None:
                for tag in ("gridSpan", "vMerge", "hMerge"):
                    for element in properties.findall(w(tag)):
                        properties.remove(element)
        for cell in cells[columns:]:
            row.remove(cell)
        cells = row.findall(w("tc"))
        while len(cells) < columns:
            extra = deepcopy(cells[-1])
            cells[-1].addnext(extra)
            cells = row.findall(w("tc"))
    if grid is None:
        grid = etree.Element(w("tblGrid"))
        copy.find(w("tblPr")).addnext(grid)
    for column in list(grid):
        grid.remove(column)
    for index in range(columns):
        width = total // columns + (total % columns if index == columns - 1 else 0)
        etree.SubElement(grid, w("gridCol")).set(w("w"), str(width))
    widths = [int(column.get(w("w"))) for column in grid]
    for row in copy.findall(w("tr")):
        for cell, width in zip(row.findall(w("tc")), widths):
            cell_width = cell.find(f"{w('tcPr')}/{w('tcW')}")
            if cell_width is not None:
                cell_width.set(w("w"), str(width))
                cell_width.set(w("type"), "dxa")
    return copy


def build_table(template: etree._Element, rows: list[list[str]], session: Session) -> etree._Element:
    """Tabel baru dengan format (tblPr, lebar kolom, baris judul dan isi) disalin dari tabel contoh."""
    template_rows = template.findall(w("tr"))
    if not template_rows:
        raise ValueError("tabel contoh kosong")
    columns = len(template_rows[0].findall(w("tc")))
    if rows and len(rows[0]) != columns and all(len(row) == len(rows[0]) for row in rows):
        template = reshaped_template(template, len(rows[0]))
        template_rows = template.findall(w("tr"))
        columns = len(rows[0])
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


def attach_comment_to_block(elements: list[etree._Element], text: str, session: Session, keep_in_clean: bool = False) -> bool:
    """Komentar yang mencakup sekumpulan paragraf/tabel hasil sisipan."""
    paragraphs = [paragraph for element in elements
                  for paragraph in ([element] if element.tag == w("p") else element.iter(w("p")))]
    filled = [paragraph for paragraph in paragraphs if [child for child in paragraph if child.tag != w("pPr")]]
    if not filled:
        return False
    first = [child for child in filled[0] if child.tag != w("pPr")][0]
    last = [child for child in filled[-1] if child.tag != w("pPr")][-1]
    attach_comment(first, last, text, session, keep_in_clean)
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
                          session: Session, width_cm: float | None = None) -> etree._Element:
    """Paragraf gambar baru: pPr dan bentuk drawing disalin dari gambar terdekat sebelumnya.

    width_cm menetapkan lebar tampil (tinggi mengikuti rasio, maks. 80% tinggi bidang teks)."""
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
    if width_cm:
        width = round(width_cm * 360000)
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


def replace_image(caption: etree._Element, image: Path, package: Package, session: Session,
                  width_cm: float | None = None) -> str:
    """Ganti gambar di atas judul: lebar tampil tetap (atau width_cm), tinggi mengikuti rasio gambar baru."""
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
    target_width = round(width_cm * 360000) if width_cm else old_size[0]
    new_size = fitted_extent(target_width, pixels, text_area(figure))
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
    heading_lists: dict[tuple[str, str], str] = field(default_factory=dict)

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
            if entry.get("rata") == "kiri":
                align_table_left(element)
            if entry.get("rata_sel") == "kiri":
                align_body_cells_left(element)
            tables += 1
        elif "gambar" in entry:
            image = session.resolve_path(entry["gambar"])
            if not image.exists():
                print(f"   ({revision.id}: gambar {image} belum tersedia; paragraf gambar dilewati)")
                continue
            width_cm = entry.get("lebar_cm")
            element = build_image_paragraph(workspace.body, anchor, image, workspace.package, session,
                                            float(width_cm) if width_cm else None)
        else:
            source = entry.get("_contoh")
            if source is None and style:
                source = nearest_with_style(workspace, anchor, style)
            if source is None:
                source = anchor
            element = build_paragraph(source, entry.get("teks", ""), session,
                                      style=style if source is anchor and style else None)
            if entry.get("nomor"):
                number_heading(element, style or "", entry["nomor"], workspace)
            if entry.get("halaman_baru"):
                properties = element.find(w("pPr"))
                if properties is None:
                    properties = etree.Element(w("pPr"))
                    element.insert(0, properties)
                if properties.find(w("pageBreakBefore")) is None:
                    place_in_order(properties, etree.Element(w("pageBreakBefore")), PARAGRAPH_PROPERTY_ORDER)
        created.append(element)
    insert_elements(anchor, created, session, before=before)
    for element in created:
        if element.tag == w("p"):
            normalise_caption(element, workspace.body, workspace.styles, session)
    if revision.extra.get("pindah_pemisah_halaman") and not before:
        move_page_break(anchor, [element for element in created if element.tag == w("p")], session)
    paragraphs = len(created) - tables
    return created, f"{paragraphs} paragraf" + (f" dan {tables} tabel" if tables else "") + " disisipkan"


def move_page_break(anchor: etree._Element, created: list[etree._Element], session: Session) -> None:
    """Pemisah halaman di akhir paragraf jangkar dipindah ke akhir sisipan (dihapus/disisipkan terlacak).

    Dipakai bila bagian baru disisipkan di akhir bab yang paragraf terakhirnya memuat pemisah halaman
    sebelum bab berikutnya, agar bab berikutnya tetap mulai di halaman baru.
    """
    if not created:
        raise ValueError("pindah_pemisah_halaman butuh paragraf sisipan")
    runs = [child for child in anchor if child.tag == w("r")]
    trailing = []
    for run in reversed(runs):
        content = [child for child in run if child.tag != w("rPr")]
        if content and all(child.tag == w("br") and child.get(w("type")) == "page" for child in content):
            trailing.append(run)
        elif "".join(run.itertext()).strip() or content:
            break
    if not trailing:
        raise ValueError("paragraf jangkar tidak diakhiri pemisah halaman")
    for run in trailing:
        if session.tracked:
            mark_deleted(run, session)
        else:
            run.getparent().remove(run)
    page_break = etree.Element(w("r"))
    etree.SubElement(page_break, w("br")).set(w("type"), "page")
    target = created[-1]
    if session.tracked:
        wrapper = etree.SubElement(target, w("ins"), revision_attributes(session))
        wrapper.append(page_break)
    else:
        target.append(page_break)


def number_heading(paragraph: etree._Element, style: str, level_format: str, workspace: Workspace) -> None:
    """Judul sisipan bernomor otomatis dengan format sendiri (mis. "2.8.%1"), meniru judul bernomor bergaya sama.

    Definisi daftar disalin dari judul bergaya sama pertama di naskah yang bernomor otomatis; level 0
    diberi format baru. Judul lain dengan gaya dan format yang sama memakai daftar yang sama sehingga
    nomornya berurutan (2.8.1, 2.8.2, ...).
    """
    template = next((candidate for candidate in workspace.body.iter(w("p"))
                     if style_matches(workspace, candidate, style)
                     and candidate.find(f"{w('pPr')}/{w('numPr')}/{w('numId')}") is not None), None)
    if template is None:
        raise ValueError(f"tidak ada judul bergaya {style} bernomor otomatis untuk dicontoh")
    key = (style, level_format)
    if key not in workspace.heading_lists:
        numbering = workspace.package.numbering()
        old_id = template.find(f"{w('pPr')}/{w('numPr')}/{w('numId')}").get(w("val"))
        num = next(item for item in numbering.findall(w("num")) if item.get(w("numId")) == old_id)
        abstract_id = num.find(w("abstractNumId")).get(w("val"))
        abstract = next(item for item in numbering.findall(w("abstractNum")) if item.get(w("abstractNumId")) == abstract_id)
        new_abstract = deepcopy(abstract)
        new_abstract_id = str(max(int(item.get(w("abstractNumId"))) for item in numbering.findall(w("abstractNum"))) + 1)
        new_abstract.set(w("abstractNumId"), new_abstract_id)
        fresh_nsid(new_abstract, f"{style}{level_format}")
        for element in new_abstract.findall(w("tmpl")):
            new_abstract.remove(element)
        level = next(item for item in new_abstract.findall(w("lvl")) if item.get(w("ilvl")) == "0")
        level.find(w("lvlText")).set(w("val"), level_format)
        start = level.find(w("start"))
        if start is not None:
            start.set(w("val"), "1")
        numbering.findall(w("abstractNum"))[-1].addnext(new_abstract)
        new_num_id = str(max(int(item.get(w("numId"))) for item in numbering.findall(w("num"))) + 1)
        new_num = etree.SubElement(numbering, w("num"))
        new_num.set(w("numId"), new_num_id)
        etree.SubElement(new_num, w("abstractNumId")).set(w("val"), new_abstract_id)
        numbering.findall(w("num"))[-2].addnext(new_num)
        workspace.heading_lists[key] = new_num_id
    properties = paragraph.find(w("pPr"))
    if properties is None:
        properties = etree.Element(w("pPr"))
        paragraph.insert(0, properties)
    for child in list(properties):
        if child.tag not in (w("rPr"), w("pPrChange")):
            properties.remove(child)
    template_properties = template.find(w("pPr"))
    for child in template_properties:
        if child.tag in (w("rPr"), w("pPrChange"), w("sectPr")):
            continue
        copied = deepcopy(child)
        if copied.tag == w("numPr"):
            for item in list(copied):
                copied.remove(item)
            etree.SubElement(copied, w("ilvl")).set(w("val"), "0")
            etree.SubElement(copied, w("numId")).set(w("val"), workspace.heading_lists[key])
        place_in_order(properties, copied, PARAGRAPH_PROPERTY_ORDER)


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
    width = revision.extra.get("lebar_cm")
    description = replace_image(revision.target.paragraph, image, workspace.package, workspace.session,
                                float(width) if width else None)
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


def without_empty_paragraphs(elements: list[etree._Element]) -> list[etree._Element]:
    """Buang paragraf kosong (jarak ketikan) kecuali yang memisahkan dua tabel agar tidak menyatu."""
    def empty(element: etree._Element) -> bool:
        return (element.tag == w("p") and not paragraph_plain_text(element).strip() and not has_drawing(element)
                and element.find(f".//{w('sectPr')}") is None and element.find(f".//{w('br')}") is None)

    kept: list[etree._Element] = []
    for index, element in enumerate(elements):
        if empty(element):
            following = next((other for other in elements[index + 1:] if not empty(other)), None)
            if not (kept and kept[-1].tag == w("tbl") and following is not None and following.tag == w("tbl")):
                continue
        kept.append(element)
    return kept


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
        elements = section_elements(source, section, markers, bool(settings.get("tanpa_penanda")))
        if settings.get("tanpa_paragraf_kosong"):
            elements = without_empty_paragraphs(elements)
        for element in elements:
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


# ---------------------------------------------------------------------------
# Sitasi Mendeley (ADDIN CSL_CITATION) dan daftar pustaka APA 7.
# ---------------------------------------------------------------------------

CSL_SCHEMA = "https://github.com/citation-style-language/schema/raw/master/csl-citation.json"
BULAN = ("Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober",
         "November", "Desember")
TAHUN_RE = r"(?:(?:19|20)\d\d[a-z]?|t\.t\.)"
LOKATOR_RE = r"(?:hlm\.|hal\.|h\.|p\.|pp\.|para\.|Tabel|Table|Gambar|Figure|Bab|bab)\s*[^;()\x00\x01\x02]*?"
MASKED = "\x02"
KATA_KECIL = {
    "a", "an", "the", "and", "but", "or", "nor", "for", "so", "yet", "as", "at", "by", "in", "of", "off", "on",
    "per", "to", "up", "via", "dan", "atau", "di", "ke", "dari", "untuk", "yang", "pada", "dengan", "bagi", "serta",
}
JENIS_BAB = {"chapter", "paper-conference", "entry-encyclopedia", "entry-dictionary"}
JENIS_TANGGAL_LENGKAP = {"webpage", "post-weblog", "post", "article-newspaper", "article-magazine"}


@dataclass
class Work:
    """Satu karya di pustaka: itemData CSL beserta tautan pustaka Mendeley (uris) bila ada."""

    key: str
    item: dict
    uris: list[str] = field(default_factory=list)
    new: bool = False
    fixed: bool = False
    fix_id: str = ""
    aliases: set[str] = field(default_factory=set)
    suffix: str = ""


@dataclass
class CitationJob:
    """Satu sitasi yang akan ditulis sebagai field CSL_CITATION di rentang [start, end) paragraf."""

    paragraph: etree._Element
    start: int
    end: int
    works: list[Work]
    mode: str
    data: dict | None = None
    old_result: str = ""
    locator: bool = False
    converted: bool = False


def csl_year(item: dict) -> str:
    parts = (item.get("issued") or {}).get("date-parts") or []
    if parts and parts[0] and str(parts[0][0]).strip():
        return str(parts[0][0]).strip()
    return "t.t."


def name_family(name: dict) -> str:
    family = (name.get("family") or name.get("literal") or "").strip()
    particle = (name.get("non-dropping-particle") or "").strip()
    return f"{particle} {family}" if particle and family else family


def name_initials(given: str) -> str:
    initials = []
    spaced = re.sub(r"\.\s*-\s*", ".-", (given or "").replace(".", ". "))
    for word in re.split(r"\s+", spaced.strip()):
        pieces = [piece.strip(".") for piece in word.split("-") if piece.strip(".")]
        if pieces:
            initials.append("-".join(piece[0].upper() + "." for piece in pieces))
    return " ".join(initials)


def authors_of(item: dict) -> list[dict]:
    """Penulis karya; bila tidak ada, editor (mis. RFC yang disusun editor)."""
    names = item.get("author") or item.get("editor") or []
    return [name for name in names if name_family(name)]


def editor_label(item: dict) -> str:
    if item.get("author") or not item.get("editor"):
        return ""
    return " (Ed.)." if len(authors_of(item)) == 1 else " (Eds.)."


def short_authors(item: dict, connector: str = "&") -> str | None:
    families = [name_family(name) for name in authors_of(item)]
    if not families:
        return None
    if len(families) == 1:
        return families[0]
    if len(families) == 2:
        return f"{families[0]} {connector} {families[1]}"
    return f"{families[0]} et al."


def reference_authors(names: list[dict]) -> str:
    formatted = []
    for name in names:
        initials = name_initials(name.get("given", ""))
        formatted.append(f"{name_family(name)}, {initials}" if initials else name_family(name))
    if len(formatted) == 1:
        text = formatted[0]
    elif len(formatted) <= 20:
        text = ", ".join(formatted[:-1]) + ", & " + formatted[-1]
    else:
        text = ", ".join(formatted[:19]) + ", . . . " + formatted[-1]
    return text if text.endswith(".") else text + "."


def protect_terms(text: str, config: dict, original: str | None = None) -> str:
    """Kembalikan ejaan baku istilah (Internet of Things, IoT, ESP32, ...) tanpa melihat huruf besar/kecil.

    Bila judul asli sudah menulis istilah itu dengan huruf kecil semua (mis. "a user experience
    questionnaire" sebagai frasa umum), ejaan asli dipertahankan.
    """
    for term in sorted(config.get("ejaan_baku", []), key=len, reverse=True):
        def restore(found: re.Match[str], term: str = term) -> str:
            if original is not None and len(original) == len(text):
                source = original[found.start():found.end()]
                if source == source.lower() and source != term:
                    return found.group(0)
            return term
        text = re.sub(rf"(?<!\w){re.escape(term)}(?!\w)", restore, text, flags=re.IGNORECASE)
    return text


def tidy_title(title: str, config: dict) -> str:
    text = re.sub(r"\s+", " ", title or "").strip()
    for old, new in config.get("ejaan_judul", {}).items():
        text = text.replace(old, new)
    text = re.sub(r"\s*‑\s*", "-", text)
    text = re.sub(r"\s+([:;,.?!])", r"\1", text)
    text = re.sub(r"([(\[])\s+", r"\1", text)
    text = re.sub(r"\s+([)\]])", r"\1", text)
    text = re.sub(r"\)\s+-(?=\w)", ")-", text)
    return text


def capitalise_first(token: str) -> str:
    for index, char in enumerate(token):
        if char.isalpha():
            return token[:index] + char.upper() + token[index + 1:]
    return token


def sentence_case(title: str, config: dict) -> str:
    """Judul artikel/buku APA 7: huruf besar hanya di awal, sesudah titik dua, nama diri dan akronim."""
    text = tidy_title(title, config)
    letters = [char for char in text if char.isalpha()]
    shouting = bool(letters) and sum(char.isupper() for char in letters) / len(letters) > 0.8
    output: list[str] = []
    capitalise_next = True
    for token in re.split(r"(\s+|-|/)", text):
        if not token or token.isspace() or token in ("-", "/"):
            output.append(token)
            continue
        core = token.strip("()[]{}\"'“”‘’.,;:!?")
        if capitalise_next:
            output.append(capitalise_first(token.lower() if shouting else token))
        elif not shouting and (any(char.isdigit() for char in core) or (len(core) > 1 and core[1:] != core[1:].lower())
                               or (len(core) == 1 and core.isupper() and core != "A")):
            output.append(token)
        else:
            output.append(token.lower())
        capitalise_next = bool(re.search(r"[:?!]$", token.rstrip("\"'”’)")))
    return protect_terms("".join(output), config, text)


def title_case(text: str, config: dict) -> str:
    """Nama jurnal dan judul di teks (APA 7 title case): kata utama berhuruf besar, kata tugas pendek kecil."""
    text = tidy_title(text, config)
    output: list[str] = []
    first = True
    for token in re.split(r"(\s+)", text):
        if not token or token.isspace():
            output.append(token)
            continue
        parts = []
        for index, part in enumerate(token.split("-")):
            core = part.strip("()[]{}\"'“”‘’.,;:!?")
            if not core:
                parts.append(part)
            elif core.lower() in KATA_KECIL and not first and index == 0 and core[1:] == core[1:].lower():
                parts.append(part.lower())
            else:
                parts.append(capitalise_first(part))
        output.append("-".join(parts))
        first = token.endswith(":")
    return protect_terms("".join(output), config, text)


def merge_pieces(pieces: list[tuple[str, bool]]) -> list[tuple[str, bool]]:
    merged: list[tuple[str, bool]] = []
    for text, italic in pieces:
        if not text:
            continue
        if merged and merged[-1][1] == italic:
            merged[-1] = (merged[-1][0] + text, italic)
        else:
            merged.append((text, italic))
    return merged


def pieces_text(pieces: list[tuple[str, bool]]) -> str:
    return "".join(text for text, _ in pieces)


def pieces_html(pieces: list[tuple[str, bool]]) -> str:
    return "".join(f"<i>{text}</i>" if italic else text for text, italic in merge_pieces(pieces))


def date_label(item: dict, suffix: str) -> str:
    year = csl_year(item)
    label = year + (("-" if year == "t.t." else "") + suffix if suffix else "")
    parts = ((item.get("issued") or {}).get("date-parts") or [[]])[0]
    if item.get("type") in JENIS_TANGGAL_LENGKAP and year != "t.t." and len(parts) >= 2 and str(parts[1]).isdigit():
        month = BULAN[int(parts[1]) - 1]
        label += f", {int(parts[2])} {month}" if len(parts) >= 3 and str(parts[2]).isdigit() else f", {month}"
    return label


def link_of(item: dict) -> str | None:
    doi = (item.get("DOI") or "").strip()
    if doi:
        doi = re.sub(r"^(?:https?://(?:dx\.)?doi\.org/|doi:\s*)", "", doi, flags=re.IGNORECASE)
        return f"https://doi.org/{doi}"
    url = (item.get("URL") or "").strip()
    return url.replace(" ", "%20") if url else None


def render_reference(work: Work, config: dict) -> list[tuple[str, bool]]:
    """Entri daftar pustaka APA 7 dari itemData CSL (bahasa Indonesia: Dalam, hlm., t.t., Versi)."""
    item = work.item
    kind = item.get("type") or "article-journal"
    container = (item.get("container-title") or "").strip()
    title = sentence_case(item.get("title", ""), config)
    title_italic = not (kind in JENIS_BAB or kind.startswith("article"))
    title_part: list[tuple[str, bool]] = [(title, title_italic)]
    details = []
    edition = str(item.get("edition") or "").strip()
    if edition:
        details.append(f"Ed. ke-{edition}" if edition.isdigit() else edition)
    if item.get("version"):
        details.append(f"Versi {item['version']}")
    if item.get("number") and kind not in JENIS_BAB and not kind.startswith("article"):
        details.append(str(item["number"]))
    if details:
        title_part.append((f" ({'; '.join(details)})", False))
    publisher = (item.get("publisher") or "").strip()
    if item.get("genre"):
        genre = item["genre"] + (f", {publisher}" if kind == "thesis" and publisher else "")
        title_part.append((f" [{genre}]", False))
    closing = "" if re.search(r"[?!]$", pieces_text(title_part)) else "."
    date = f"({date_label(item, work.suffix)})."
    pieces: list[tuple[str, bool]] = []
    authors = authors_of(item)
    if authors:
        names = reference_authors(authors)
        if editor_label(item):
            names = names + editor_label(item)
        pieces.append((f"{names} {date} ", False))
        pieces.extend(title_part)
        pieces.append((f"{closing} ", False))
    else:
        pieces.extend(title_part)
        pieces.append((f"{closing} {date} ", False))

    volume, issue = str(item.get("volume") or "").strip(), str(item.get("issue") or "").strip()
    pages = str(item.get("page") or "").strip().replace("-", "–")
    if kind == "thesis" and item.get("genre"):
        publisher = ""
    if kind.startswith("article"):
        source: list[tuple[str, bool]] = []
        if container:
            source.append((title_case(container, config), True))
        if volume:
            source.append((", " if container else "", False))
            source.append((volume, True))
        if issue:
            source.append((f"({issue})", False))
        if pages:
            source.append((", " if source else "", False))
            source.append((pages, False))
        elif item.get("number"):
            source.append((", " if source else "", False))
            source.append((f"Artikel {item['number']}", False))
        if source:
            pieces.extend(source)
            pieces.append((". ", False))
    elif kind in JENIS_BAB:
        editors = [name for name in item.get("editor") or [] if name_family(name)]
        if editors or container:
            pieces.append(("Dalam ", False))
            if editors:
                names = [f"{name_initials(name.get('given', ''))} {name_family(name)}".strip() for name in editors]
                joined = names[0] if len(names) == 1 else ", ".join(names[:-1]) + ", & " + names[-1]
                pieces.append((f"{joined} ({'Ed.' if len(names) == 1 else 'Eds.'}), ", False))
            pieces.append((sentence_case(container, config), True))
            inside = [part for part in (item.get("collection-title"), f"Vol. {volume}" if volume else None,
                                        f"hlm. {pages}" if pages else None) if part]
            if inside:
                pieces.append((f" ({', '.join(inside)})", False))
            pieces.append((". ", False))
        if publisher:
            pieces.append((f"{publisher}. ", False))
    elif publisher and publisher.rstrip(".") != (short_authors(item) or "").rstrip("."):
        pieces.append((f"{publisher}. ", False))
    link = link_of(item)
    if link:
        pieces.append((link, False))
    pieces = merge_pieces(pieces)
    if pieces and pieces[-1][0].endswith(" "):
        pieces[-1] = (pieces[-1][0].rstrip(), pieces[-1][1])
    return pieces


def reference_sort_key(work: Work) -> tuple:
    """Urutan Mendeley/APA: nama penulis (huruf demi huruf), tahun, huruf a/b/c, judul."""
    authors = authors_of(work.item)
    if authors:
        names = tuple(f"{name_family(name)}, {name_initials(name.get('given', ''))}".lower() for name in authors)
    else:
        names = (re.sub(r"^(the|a|an)\s+", "", (work.item.get("title") or "").lower()),)
    year = csl_year(work.item)
    return (names, year if year != "t.t." else "0000", work.suffix, (work.item.get("title") or "").lower())


def in_text_label(work: Work, connector: str, config: dict) -> tuple[str, bool]:
    label = short_authors(work.item, connector)
    if label:
        return label, False
    title = title_case(work.item.get("title", ""), config)
    kind = work.item.get("type") or ""
    if kind in JENIS_BAB or kind.startswith("article"):
        return f"“{title}”", False
    return title, True


def citation_pieces(works: list[Work], mode: str, config: dict) -> list[tuple[str, bool]]:
    """mode: "kurung" (Nama, 2020), "naratif" Nama (2020), "penulis" Nama (kolom tahun terpisah)."""
    connector = "&" if mode == "kurung" else config.get("penghubung_naratif", "dan")
    groups: list[tuple[tuple[str, bool], list[str]]] = []
    for work in works:
        label = in_text_label(work, connector, config)
        year = csl_year(work.item) + (("-" if csl_year(work.item) == "t.t." else "") + work.suffix if work.suffix else "")
        if groups and groups[-1][0] == label:
            groups[-1][1].append(year)
        else:
            groups.append((label, [year]))
    pieces: list[tuple[str, bool]] = []
    if mode == "kurung":
        pieces.append(("(", False))
        for index, ((label, italic), years) in enumerate(groups):
            if index:
                pieces.append(("; ", False))
            pieces.append((label, italic))
            pieces.append((", " + ", ".join(years), False))
        pieces.append((")", False))
    else:
        for index, ((label, italic), years) in enumerate(groups):
            if index:
                pieces.append(("; ", False))
            pieces.append((label, italic))
            if mode == "naratif":
                pieces.append((" (" + ", ".join(years) + ")", False))
    return merge_pieces(pieces)


def work_key(item: dict) -> str:
    title = re.sub(r"\W+", "", (item.get("title") or "").lower())
    if not title:
        title = re.sub(r"\W+", "", (short_authors(item) or "").lower())
    return f"{title[:60]}|{csl_year(item)}"


def mendeley_item(data: dict, identifier: str) -> dict:
    """itemData dengan bentuk yang sama seperti tulisan Mendeley (kunci nama lengkap, tahun berupa teks, kunci urut)."""
    item = deepcopy(data)
    item["id"] = identifier
    for role in ("author", "editor"):
        if role in item:
            item[role] = [dict(sorted({"dropping-particle": "", "given": "", "non-dropping-particle": "", "parse-names": False,
                                       "suffix": "", **name}.items())) for name in item[role]]
    if "issued" in item:
        item["issued"] = {"date-parts": [[str(value) for value in parts] for parts in item["issued"]["date-parts"]]}
    return dict(sorted(item.items()))


class Library:
    """Karya yang dikenal: dari field Mendeley di naskah (itemData + uris) dan dari data pustaka baru."""

    def __init__(self, config: dict) -> None:
        self.config = config
        self.works: dict[str, Work] = {}

    def add_from_field(self, data: dict) -> None:
        for citation in data.get("citationItems", []):
            item = citation.get("itemData") or {}
            key = work_key(item)
            if key not in self.works:
                self.works[key] = Work(key=key, item=deepcopy(item), uris=list(citation.get("uris") or []))

    def add_new(self, entry: dict) -> None:
        item = mendeley_item(entry["itemData"], entry["id"])
        key = work_key(item)
        if key in self.works:
            # Naskah hasil pembangunan sebelumnya sudah memuat karya ini sebagai field:
            # pakai data di naskah, dan anggap tetap "baru" selama belum tertaut ke Mendeley.
            existing = self.works[key]
            existing.aliases.update(entry.get("alias", []))
            existing.new = not existing.uris
            return
        work = Work(key=key, item=item, new=True)
        work.aliases.update(entry.get("alias", []))
        self.works[key] = work

    def matching(self, criteria: dict) -> list[Work]:
        found = []
        for work in self.works.values():
            authors = authors_of(work.item)
            family = name_family(authors[0]) if authors else None
            if "family" in criteria and criteria["family"] != family:
                continue
            if "tahun" in criteria and str(criteria["tahun"]) != csl_year(work.item):
                continue
            if "judul" in criteria and criteria["judul"].lower() not in (work.item.get("title") or "").lower():
                continue
            found.append(work)
        return found

    def apply_fixes(self, fixes: list[dict]) -> list[str]:
        notes = []
        for fix in fixes:
            works = self.matching(fix.get("cocok", {}))
            if len(works) != 1:
                notes.append(f"perbaikan {fix.get('cocok')} cocok dengan {len(works)} karya; dilewati")
                continue
            work = works[0]
            if fix.get("itemData"):
                old_key = work.key
                work.item.update(deepcopy(fix["itemData"]))
                for name in [name for name, value in fix["itemData"].items() if value is None]:
                    del work.item[name]
                work.fixed = True
                work.fix_id = fix.get("kunci", "")
                if work_key(work.item) != old_key:
                    self.works[work_key(work.item)] = work
                    work.aliases.add(f"__kunci_lama__{old_key}")
            work.aliases.update(fix.get("alias", []))
        return notes

    def lookup(self, item: dict) -> Work | None:
        key = work_key(item)
        if key in self.works:
            return self.works[key]
        return next((work for work in self.works.values() if f"__kunci_lama__{key}" in work.aliases), None)

    def unique(self) -> list[Work]:
        return list({id(work): work for work in self.works.values()}.values())

    def alias_index(self) -> dict[str, list[Work]]:
        index: dict[str, list[Work]] = {}
        for work in self.unique():
            forms = {alias for alias in work.aliases if not alias.startswith("__")}
            for connector in ("&", "dan", "and"):
                label = short_authors(work.item, connector)
                if label:
                    forms.add(label)
            if not authors_of(work.item) and work.item.get("title"):
                forms.update({title_case(work.item["title"], self.config), work.item["title"],
                              sentence_case(work.item["title"], self.config)})
            for form in forms:
                index.setdefault(form, []).append(work)
        return index

    def resolve(self, candidates: list[Work], token: str) -> Work | None:
        """Karya untuk penanda tahun di teks ("2026", "2026b", "t.t."); huruf = urutan judul di kelompok."""
        found = re.fullmatch(r"((?:19|20)\d\d|t\.t\.)-?([a-z]?)", token)
        if not found:
            return None
        year, letter = found.groups()
        same_year = [work for work in candidates if csl_year(work.item) == year]
        if not letter:
            return same_year[0] if len(same_year) == 1 else None
        groups: dict[str, list[Work]] = {}
        for work in self.unique():
            label = short_authors(work.item) or work.item.get("title", "")
            if csl_year(work.item) == year:
                groups.setdefault(label, []).append(work)
        for work in same_year:
            group = sorted(groups.get(short_authors(work.item) or work.item.get("title", ""), []),
                           key=lambda other: (other.item.get("title") or "").lower())
            position = ord(letter) - ord("a")
            if len(group) > 1 and position < len(group) and group[position] is work:
                return work
        return None


def field_spans(segments: list[Segment]) -> tuple[list[tuple[int, int, str]], list[bool]]:
    """Field utuh di paragraf [(awal, akhir, instruksi)] dan penanda karakter yang berada di dalam field."""
    spans: list[tuple[int, int, str]] = []
    total = segments[-1].end if segments else 0
    inside = [False] * total
    depth = 0
    start = 0
    instruction: list[str] = []
    for segment in segments:
        if segment.kind == "field":
            for child in segment.run:
                if child.tag == w("fldChar"):
                    kind = child.get(w("fldCharType"))
                    if kind == "begin":
                        if depth == 0:
                            start, instruction = segment.start, []
                        depth += 1
                    elif kind == "end":
                        depth = max(0, depth - 1)
                        if depth == 0:
                            spans.append((start, segment.end, "".join(instruction)))
                elif child.tag == w("instrText") and depth == 1:
                    instruction.append(child.text or "")
            for position in range(segment.start, segment.end):
                inside[position] = True
        elif depth > 0:
            for position in range(segment.start, segment.end):
                inside[position] = True
    return spans, inside


def masked_text(paragraph: etree._Element) -> tuple[str, list[tuple[int, int, str]]]:
    segments, text = paragraph_model(paragraph)
    spans, inside = field_spans(segments)
    masked = "".join(MASKED if inside[index] else char for index, char in enumerate(text))
    return masked, spans


def parse_csl(instruction: str) -> dict | None:
    if "CSL_CITATION" not in instruction or "{" not in instruction:
        return None
    try:
        return json.loads(instruction[instruction.index("{"):])
    except json.JSONDecodeError:
        return None


def field_runs(template: etree._Element, instruction: str, pieces: list[tuple[str, bool]]) -> list[etree._Element]:
    def char_run(kind: str, lock: bool = False) -> etree._Element:
        run = make_run(template, [], fresh=True)
        attributes = {w("fldCharType"): kind}
        if lock:
            attributes[w("fldLock")] = "1"
        run.append(etree.Element(w("fldChar"), attributes))
        return run

    instruction_run = make_run(template, [], fresh=True)
    element = etree.SubElement(instruction_run, w("instrText"))
    element.text = instruction
    element.set(f"{{{XML_NS}}}space", "preserve")
    result = [make_run(template, list(text), italic, fresh=True) for text, italic in merge_pieces(pieces)]
    return [char_run("begin", lock=True), instruction_run, char_run("separate"), *result, char_run("end")]


def place_runs(runs: list[etree._Element], new_runs: list[etree._Element], session: Session) -> tuple[etree._Element, etree._Element]:
    """Ganti run lama dengan run baru: run lama ke w:del, run baru dalam satu w:ins (Track Changes)."""
    if not session.tracked or all(inside_insertion(run) for run in runs):
        # Tanpa Track Changes, atau teks sisipan sendiri: ganti di tempat (w:ins tidak boleh bersarang).
        if not new_runs:
            new_runs = [etree.Element(w("r"))]
        for new_run in new_runs:
            runs[0].addprevious(new_run)
        for run in runs:
            run.getparent().remove(run)
        return new_runs[0], new_runs[-1]
    first = last = None
    anchor = runs[0]
    marker = etree.Element(w("r"))
    anchor.addprevious(marker)
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
        (last if last is not None else marker).addnext(inserted)
        first = first if first is not None else inserted
        last = inserted
    marker.getparent().remove(marker)
    return first, last


def build_citation_data(works: list[Work], mode: str, config: dict, old: dict | None = None) -> tuple[dict, list[tuple[str, bool]]]:
    """JSON CSL_CITATION (format Mendeley) dan isi tampilannya untuk karya terurut."""
    parenthetical = citation_pieces(works, "kurung", config)
    visible = parenthetical if mode == "kurung" else citation_pieces(works, mode, config)
    if old is not None:
        data = deepcopy(old)
        by_key = {}
        for citation in data.get("citationItems", []):
            by_key.setdefault(work_key(citation.get("itemData") or {}), citation)
        items = []
        for work in works:
            citation = by_key.get(work_key(work.item)) or next(
                (value for key, value in by_key.items() if f"__kunci_lama__{key}" in work.aliases), None)
            if work.fixed:
                identifier = (citation.get("itemData") or {}).get("id", citation.get("id"))
                citation["itemData"] = {**deepcopy(work.item), "id": identifier}
            for key in ("locator", "label"):
                citation.pop(key, None)
            items.append(citation)
        data["citationItems"] = items
    else:
        items = []
        for number, work in enumerate(works, start=1):
            identifier = work.item.get("id") if work.new else f"ITEM-{number}"
            citation = {"id": identifier, "itemData": {**deepcopy(work.item), "id": identifier}}
            if work.uris:
                citation["uris"] = list(work.uris)
            items.append(citation)
        data = {"citationItems": items, "mendeley": {}, "properties": {"noteIndex": 0}, "schema": CSL_SCHEMA}
    mendeley = dict(data.get("mendeley") or {})
    html = pieces_html(parenthetical)
    mendeley.update({"formattedCitation": html, "plainTextFormattedCitation": pieces_text(parenthetical),
                     "previouslyFormattedCitation": html})
    if mode == "kurung":
        mendeley.pop("manualFormatting", None)
    else:
        mendeley["manualFormatting"] = pieces_text(visible)
    data["mendeley"] = dict(sorted(mendeley.items()))
    return data, visible


def classify_manual(manual: str | None) -> str:
    if not manual:
        return "kurung"
    if re.fullmatch(r".+ \(.+\)", manual.strip()):
        return "naratif"
    if re.search(TAHUN_RE, manual):
        return "kurung"
    return "penulis"


def table_row_years(paragraph: etree._Element) -> list[str]:
    cell = next((ancestor for ancestor in paragraph.iterancestors(w("tc"))), None)
    row = cell.getparent() if cell is not None else None
    if row is None:
        return []
    years = []
    for other in row.iter(w("p")):
        text = live_text(other).strip()
        if re.fullmatch(TAHUN_RE, text):
            years.append(text)
    return years


def citation_jobs(paragraph: etree._Element, library: Library, aliases: dict[str, list[Work]],
                  patterns: dict[str, re.Pattern[str]], config: dict, notes: list[str]) -> list[CitationJob]:
    masked, spans = masked_text(paragraph)
    jobs: list[CitationJob] = []
    _, raw = paragraph_model(paragraph)
    for start, end, instruction in spans:
        data = parse_csl(instruction)
        if data is None:
            continue
        works = []
        for citation in data.get("citationItems", []):
            work = library.lookup(citation.get("itemData") or {})
            if work is None:
                notes.append(f"karya di field tidak dikenal: {(citation.get('itemData') or {}).get('title')}")
                break
            works.append(work)
        else:
            mode = classify_manual((data.get("mendeley") or {}).get("manualFormatting"))
            locator = any("locator" in citation for citation in data.get("citationItems", []))
            jobs.append(CitationJob(paragraph, start, end, works, mode, data=data,
                                    old_result=raw[start:end].replace(FIELD, ""), locator=locator))

    def works_for(alias: str, years: str) -> list[Work] | None:
        found = []
        for token in re.findall(TAHUN_RE, years):
            work = library.resolve(aliases.get(alias, []), token)
            if work is None:
                return None
            found.append(work)
        return found or None

    taken: list[tuple[int, int]] = []

    def free(start: int, end: int) -> bool:
        return all(end <= other_start or start >= other_end for other_start, other_end in taken)

    for match in patterns["kurung"].finditer(masked):
        works: list[Work] = []
        locator = False
        for part in match.group("isi").split(";"):
            found = patterns["bagian"].fullmatch(part.strip())
            resolved = works_for(found.group("alias"), found.group("tahun")) if found else None
            if resolved is None:
                works = []
                break
            works.extend(resolved)
            locator = locator or bool(found.group("lokator"))
        if works:
            jobs.append(CitationJob(paragraph, match.start(), match.end(), works, "kurung", locator=locator, converted=True))
            taken.append((match.start(), match.end()))
        elif re.search(r"\b(?:19|20)\d\d[a-z]?\b|t\.t\.", match.group("isi")) and re.search(r"[A-Z]", match.group("isi")):
            notes.append(f"sitasi dalam kurung tidak dikenali: {match.group(0)[:90]}")
    for name in ("naratif", "sumber"):
        for match in patterns[name].finditer(masked):
            start, end = match.start("sitasi"), match.end("sitasi")
            if not free(start, end):
                continue
            works = works_for(match.group("alias"), match.group("tahun"))
            if works is None:
                notes.append(f"sitasi naratif tidak dikenali: {match.group('sitasi')[:90]}")
                continue
            jobs.append(CitationJob(paragraph, start, end, works, "naratif" if name == "naratif" else "kurung",
                                    locator=bool(match.group("lokator")), converted=True))
            taken.append((start, end))
    stripped = masked.strip()
    if stripped in aliases and not spans:
        years = table_row_years(paragraph)
        works = works_for(stripped, years[0]) if len(years) == 1 else None
        if works:
            offset = masked.index(stripped)
            jobs.append(CitationJob(paragraph, offset, offset + len(stripped), works, "penulis", converted=True))
    return jobs


def citation_patterns(aliases: dict[str, list[Work]], config: dict) -> dict[str, re.Pattern[str]]:
    names = "|".join(re.escape(alias) for alias in sorted(aliases, key=len, reverse=True))
    years = rf"{TAHUN_RE}(?:,\s*{TAHUN_RE})*"
    prefixes = "|".join(re.escape(prefix) for prefix in config.get("awalan_sumber", ["Sumber"]))
    return {
        "kurung": re.compile(r"\((?P<isi>[^()\x00\x01\x02]*?(?:\d{4}[a-z]?|t\.t\.)[^()\x00\x01\x02]*?)\)"),
        "bagian": re.compile(rf"(?P<alias>{names}),\s*(?P<tahun>{years})(?P<lokator>,\s*{LOKATOR_RE})?"),
        "naratif": re.compile(rf"(?<![\w.])(?P<sitasi>(?P<alias>{names}) \((?P<tahun>{years})(?P<lokator>,\s*{LOKATOR_RE})?\))"),
        "sumber": re.compile(rf"(?:{prefixes})\s*:\s*(?P<sitasi>(?P<alias>{names}),\s*(?P<tahun>{years})(?P<lokator>,\s*{LOKATOR_RE})?)\s*$"),
    }


def bibliography_field(body: etree._Element) -> tuple[list[etree._Element], etree._Element, etree._Element] | None:
    """Paragraf entri daftar pustaka Mendeley, paragraf awal field, dan paragraf penutup field."""
    paragraphs = list(body.iter(w("p")))
    for index, paragraph in enumerate(paragraphs):
        instruction = "".join(element.text or "" for run in iter_runs(paragraph) if run.tag == w("r")
                              for element in run.findall(w("instrText")))
        if "CSL_BIBLIOGRAPHY" not in instruction:
            continue
        for end_index in range(index, len(paragraphs)):
            candidate = paragraphs[end_index]
            ends = [char for run in iter_runs(candidate) if run.tag == w("r")
                    for char in run.findall(w("fldChar")) if char.get(w("fldCharType")) == "end"]
            if ends and end_index > index:
                return paragraphs[index:end_index], paragraph, candidate
        return None
    return None


def entry_runs(paragraph: etree._Element, first: bool) -> list[etree._Element]:
    runs = [run for run in iter_runs(paragraph) if run.tag == w("r")]
    if first:
        separate = next((index for index, run in enumerate(runs)
                         if any(char.get(w("fldCharType")) == "separate" for char in run.findall(w("fldChar")))), None)
        runs = runs[separate + 1:] if separate is not None else []
    return [run for run in runs if run_kind(run)[0] == "text"]


def entry_key(runs: list[etree._Element], styles: StyleBook, paragraph: etree._Element) -> tuple:
    pieces = [("".join(run_kind(run)[1]), styles.is_italic(run, paragraph)) for run in runs]
    return tuple(merge_pieces(pieces))


def rebuild_bibliography(body: etree._Element, works: list[Work], config: dict, styles: StyleBook,
                         session: Session) -> tuple[list[str], list[str]] | None:
    """Isi field CSL_BIBLIOGRAPHY = entri APA 7 karya yang disitasi; perubahan sebagai Track Changes."""
    found = bibliography_field(body)
    if found is None:
        return None
    paragraphs, first, closing = found
    old = []
    for paragraph in paragraphs:
        if paragraph_deleted(paragraph) and paragraph is not first:
            continue
        runs = entry_runs(paragraph, paragraph is first)
        old.append((paragraph, runs, entry_key(runs, styles, paragraph)))
    new = [tuple(render_reference(work, config)) for work in works]
    template_paragraph = first
    template_run = plain_template(dominant_template(old[0][1])) if old and old[0][1] else etree.Element(w("r"))
    removed = [pieces_text(list(key)) for _, _, key in old if key not in new and key]
    added = [pieces_text(list(key)) for key in new if key not in [entry[2] for entry in old]]

    def new_runs(pieces: tuple) -> list[etree._Element]:
        return [make_run(template_run, list(text), italic, fresh=True) for text, italic in pieces]

    def new_paragraph(pieces: tuple) -> etree._Element:
        paragraph = etree.Element(w("p"))
        properties = clean_properties(template_paragraph.find(w("pPr")))
        if len(properties):
            paragraph.append(properties)
        paragraph.extend(new_runs(pieces))
        mark_paragraph_inserted(paragraph, session)
        return paragraph

    start_old = 0
    anchor = first
    new_first_kept = new_first_written = False
    if old and old[0][0] is first:
        if not new or old[0][2] != new[0]:
            if old[0][1]:
                delete_runs(old[0][1], session)
            if new:
                runs = new_runs(new[0])
                holder = runs
                if session.tracked:
                    inserted = etree.Element(w("ins"), revision_attributes(session))
                    inserted.extend(runs)
                    holder = [inserted]
                last = next((run for run in reversed(list(first)) if run.tag in (w("r"), w("ins"), w("del"))), None)
                for element in holder:
                    (last.addnext(element) if last is not None else first.append(element))
                    last = element
                new = new[1:]
                new_first_written = True
            start_old = 1
        else:
            start_old, new = 1, new[1:]
            new_first_kept = True
    old_rest = old[start_old:]
    matcher = difflib.SequenceMatcher(None, [entry[2] for entry in old_rest], new, autojunk=False)
    kept = [first] if new_first_kept or new_first_written else []
    for tag, old_from, old_to, new_from, new_to in matcher.get_opcodes():
        if tag == "equal":
            anchor = old_rest[old_to - 1][0]
            kept.extend(entry[0] for entry in old_rest[old_from:old_to])
            continue
        for paragraph, _, _ in old_rest[old_from:old_to]:
            delete_paragraph(paragraph, session)
            anchor = paragraph
        for pieces in new[new_from:new_to]:
            paragraph = new_paragraph(pieces)
            anchor.addnext(paragraph)
            anchor = paragraph
            kept.append(paragraph)
    alignment = config.get("perataan_daftar_pustaka")
    if alignment:
        for paragraph in kept:
            set_alignment(paragraph, alignment, session)
    return added, removed


def mendeley_citations(body: etree._Element, config: dict, styles: StyleBook, session: Session) -> list[str]:
    """Jadikan setiap sitasi field Mendeley APA 7 dan susun ulang daftar pustakanya dari itemData."""
    library = Library(config)
    excluded = excluded_paragraphs(body, {"kecualikan_setelah_judul": config.get("judul_daftar_pustaka", "DAFTAR PUSTAKA")})
    paragraphs = [paragraph for paragraph in body.iter(w("p"))
                  if paragraph not in excluded and not is_listing_paragraph(paragraph)]
    for paragraph in paragraphs:
        _, spans = masked_text(paragraph)
        for _, _, instruction in spans:
            data = parse_csl(instruction)
            if data is not None:
                library.add_from_field(data)
    for entry in config.get("baru", []):
        library.add_new(entry)
    notes = library.apply_fixes(config.get("perbaikan", []))
    aliases = library.alias_index()
    patterns = citation_patterns(aliases, config)

    jobs: list[CitationJob] = []
    for paragraph in paragraphs:
        jobs.extend(citation_jobs(paragraph, library, aliases, patterns, config, notes))
    cited = {id(work): work for job in jobs for work in job.works}
    groups: dict[tuple[str, str], list[Work]] = {}
    for work in cited.values():
        work.suffix = ""
        names = reference_authors(authors_of(work.item)) if authors_of(work.item) else work.item.get("title", "")
        groups.setdefault((names, csl_year(work.item)), []).append(work)
    for group in groups.values():
        if len(group) > 1:
            for index, work in enumerate(sorted(group, key=lambda item: (item.item.get("title") or "").lower())):
                work.suffix = "abcdefghijklmnopqrstuvwxyz"[index]
    ordered = sorted(cited.values(), key=reference_sort_key)
    rank = {id(work): index for index, work in enumerate(ordered)}

    counts = {"dikonversi": 0, "field diperbarui": 0, "manualFormatting diperbaiki": 0, "lokator dihapus": 0}
    by_paragraph: dict[int, list[CitationJob]] = {}
    for job in jobs:
        by_paragraph.setdefault(id(job.paragraph), []).append(job)
    for paragraph_jobs in by_paragraph.values():
        for job in sorted(paragraph_jobs, key=lambda item: item.start, reverse=True):
            works = sorted({id(work): work for work in job.works}.values(), key=lambda work: rank[id(work)])
            data, visible = build_citation_data(works, job.mode, config, job.data)
            if job.data is not None and data == job.data and pieces_text(visible) == job.old_result:
                continue
            instruction = "ADDIN CSL_CITATION " + json.dumps(data, ensure_ascii=False, separators=(",", ":"))
            segments, _ = paragraph_model(job.paragraph)
            runs = isolate(segments, job.start, job.end)
            text_runs = [run for run in runs if run.tag == w("r") and run_kind(run)[0] == "text"]
            template = plain_template(dominant_template(text_runs))
            place_runs(runs, field_runs(template, instruction, visible), session)
            counts["lokator dihapus"] += int(job.locator)
            if job.converted:
                counts["dikonversi"] += 1
            else:
                counts["field diperbarui"] += 1
                old_manual = (job.data.get("mendeley") or {}).get("manualFormatting")
                if old_manual is not None and old_manual != data["mendeley"].get("manualFormatting"):
                    counts["manualFormatting diperbaiki"] += 1
    lines = [f"  {name}: {count}" for name, count in counts.items()]
    result = rebuild_bibliography(body, ordered, config, styles, session)
    if result is None:
        lines.append("  field CSL_BIBLIOGRAPHY tidak ditemukan")
    else:
        added, removed = result
        lines.append(f"  daftar pustaka: {len(ordered)} entri; ditulis ulang/ditambah {len(added)}, dihapus/diganti {len(removed)}")
        def entry_name(text: str) -> str:
            year = re.search(r"\((\d{4}[a-z]?|t\.t\.)", text)
            return f"{re.split(r',| \(| \[', text)[0].rstrip('.')} ({year.group(1) if year else 't.t.'})"

        old_names = {entry_name(text) for text in removed}
        new_names = {entry_name(text) for text in added}
        for name in sorted(old_names - new_names):
            lines.append(f"    entri dihapus (tidak disitasi): {name}")
        for name in sorted(new_names - old_names):
            lines.append(f"    entri baru: {name}")
    for work in ordered:
        if work.new:
            lines.append(f"  karya baru tanpa tautan Mendeley (impor RIS/BibTeX): {work.item.get('id')}")
    for note in dict.fromkeys(notes):
        lines.append(f"  PERHATIKAN: {note}")
    for paragraph in paragraphs:
        masked, _ = masked_text(paragraph)
        for found in PLAIN_CITATION_RE.finditer(masked):
            lines.append(f"  PERHATIKAN sitasi teks biasa tersisa: {masked[max(0, found.start() - 30):found.end() + 10]!r}")
    session.cited_works = ordered
    session.library_config = config
    return lines


PLAIN_CITATION_RE = re.compile(
    r"et al\.|\b[A-Z][\w.\-]+(?: (?:&|dan) [A-Za-z][\w\-]+(?: [A-Z][\w\-]+)?)? \((?:19|20)\d\d[a-z]?[),]"
    r"|\([^()\x02]*?[A-Z][^()\x02]*?,? (?:19|20)\d\d[a-z]?(?:,[^()\x02]*)?\)|\bt\.t\.")


# ---------------------------------------------------------------------------
# Ekspor karya baru (RIS dan BibTeX) untuk diimpor ke Mendeley.
# ---------------------------------------------------------------------------

RIS_TYPES = {"article-journal": "JOUR", "paper-conference": "CONF", "chapter": "CHAP", "report": "RPRT",
             "webpage": "ELEC", "book": "BOOK", "thesis": "THES"}
BIBTEX_TYPES = {"article-journal": "article", "paper-conference": "inproceedings", "chapter": "incollection",
                "report": "techreport", "webpage": "misc", "book": "book", "thesis": "phdthesis"}


def export_ris(works: list[Work]) -> str:
    lines = []
    for work in works:
        item = work.item
        lines.append(f"TY  - {RIS_TYPES.get(item.get('type'), 'GEN')}")
        lines.append(f"ID  - {item.get('id')}")
        for name in item.get("author") or []:
            given = name.get("given", "")
            lines.append(f"AU  - {name_family(name)}, {given}" if given else f"AU  - {name_family(name)}")
        for name in item.get("editor") or []:
            lines.append(f"A2  - {name_family(name)}, {name.get('given', '')}".rstrip(", "))
        parts = ((item.get("issued") or {}).get("date-parts") or [[]])[0]
        if parts:
            lines.append(f"PY  - {parts[0]}")
            if len(parts) > 1:
                padded = [str(parts[0])] + [f"{int(value):02d}" for value in parts[1:]] + [""] * (3 - len(parts))
                lines.append("DA  - " + "/".join(padded) + "/")
        lines.append(f"TI  - {item.get('title', '')}")
        if item.get("container-title"):
            lines.append(f"{'JO' if item.get('type') == 'article-journal' else 'T2'}  - {item['container-title']}")
        if item.get("collection-title"):
            lines.append(f"T3  - {item['collection-title']}")
        for key, tag in (("volume", "VL"), ("issue", "IS"), ("publisher", "PB"), ("number", "SN"), ("edition", "ET"), ("version", "ET"),
                         ("genre", "M3"), ("DOI", "DO"), ("URL", "UR")):
            if item.get(key):
                lines.append(f"{tag}  - {item[key]}")
        if item.get("page"):
            first, _, last = str(item["page"]).partition("-")
            lines.append(f"SP  - {first}")
            if last:
                lines.append(f"EP  - {last}")
        lines.append("ER  - ")
        lines.append("")
    return "\n".join(lines)


def bibtex_escape(text: str) -> str:
    return re.sub(r"([&%#_$])", r"\\\1", str(text))


def export_bibtex(works: list[Work], config: dict | None = None) -> str:
    """BibTeX untuk Mendeley; dengan config, judul ditulis sentence case dan nama jurnal title case (APA 7)."""
    entries = []
    for work in works:
        item = dict(work.item, id=work.fix_id) if work.fix_id else work.item
        if config is not None:
            item = dict(item, title=sentence_case(item.get("title", ""), config))
            if item.get("container-title"):
                rendered = sentence_case if item.get("type") in JENIS_BAB else title_case
                item["container-title"] = rendered(item["container-title"], config)
        fields: list[tuple[str, str]] = []
        names = []
        for name in item.get("author") or []:
            if name.get("given"):
                names.append(f"{name_family(name)}, {name['given']}")
            else:
                names.append("{" + name_family(name) + "}")
        if names:
            fields.append(("author", " and ".join(names)))
        editors = [f"{name_family(name)}, {name.get('given', '')}".rstrip(", ") for name in item.get("editor") or []]
        if editors:
            fields.append(("editor", " and ".join(editors)))
        fields.append(("title", "{" + item.get("title", "") + "}"))
        kind = item.get("type")
        container = {"article-journal": "journal", "paper-conference": "booktitle", "chapter": "booktitle"}.get(kind)
        if container and item.get("container-title"):
            fields.append((container, item["container-title"]))
        parts = ((item.get("issued") or {}).get("date-parts") or [[]])[0]
        if parts:
            fields.append(("year", str(parts[0])))
            if len(parts) > 1:
                fields.append(("month", str(parts[1])))
            if len(parts) > 2:
                fields.append(("day", str(parts[2])))
        mapping = (("collection-title", "series"), ("volume", "volume"), ("issue", "number"), ("page", "pages"),
                   ("edition", "edition"), ("version", "edition"), ("genre", "type"), ("DOI", "doi"), ("URL", "url"))
        for key, name in mapping:
            if item.get(key):
                value = str(item[key]).replace("-", "--") if key == "page" else item[key]
                fields.append((name, value))
        if kind and kind.startswith("article") and item.get("number") and not item.get("page"):
            fields.append(("pages", str(item["number"])))
        if item.get("publisher"):
            fields.append(({"report": "institution", "thesis": "school"}.get(kind, "publisher"), item["publisher"]))
        if item.get("number") and kind == "report":
            fields = [(name, value) for name, value in fields if name != "number"] + [("number", item["number"])]
        body = ",\n".join(f"  {name} = {{{bibtex_escape(value) if name not in ('url', 'doi') else value}}}" for name, value in fields)
        entries.append(f"@{BIBTEX_TYPES.get(kind, 'misc')}{{{item.get('id')},\n{body}\n}}\n")
    return "\n".join(entries)


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


SETTINGS_AFTER_UPDATE_FIELDS = {
    "hdrShapeDefaults", "footnotePr", "endnotePr", "compat", "docVars", "rsids", "mathPr", "attachedSchema",
    "themeFontLang", "clrSchemeMapping", "doNotIncludeSubdocsInStats", "doNotAutoCompressPictures", "forceUpgrade",
    "captions", "readModeInkLockDown", "smartTagType", "schemaLibrary", "shapeDefaults", "doNotEmbedSmartTags",
    "decimalSymbol", "listSeparator",
}


def set_update_fields(files: dict[str, bytes]) -> bool:
    """w:updateFields di settings.xml: Word memperbarui Daftar Isi/Tabel/Gambar dan PAGEREF saat dibuka."""
    name = "word/settings.xml"
    if name not in files:
        return False
    root = etree.fromstring(files[name])
    for existing in root.findall(w("updateFields")):
        root.remove(existing)
    element = etree.Element(w("updateFields"), {w("val"): "true"})
    anchor = next((child for child in root if isinstance(child.tag, str) and child.tag.startswith(f"{{{W_NS}}}")
                   and etree.QName(child).localname in SETTINGS_AFTER_UPDATE_FIELDS), None)
    if anchor is not None:
        anchor.addprevious(element)
    else:
        root.append(element)
    files[name] = serialize(root)
    return True


SETTINGS_AFTER_NO_COMPRESSION = {
    "forceUpgrade", "captions", "readModeInkLockDown", "smartTagType", "schemaLibrary",
    "shapeDefaults", "doNotEmbedSmartTags", "decimalSymbol", "listSeparator",
}


def set_no_picture_compression(files: dict[str, bytes]) -> bool:
    """w:doNotAutoCompressPictures: Word tidak menurunkan resolusi gambar (mis. ke 220 ppi) saat menyimpan."""
    name = "word/settings.xml"
    if name not in files:
        return False
    root = etree.fromstring(files[name])
    if root.find(w("doNotAutoCompressPictures")) is not None:
        return True
    element = etree.Element(w("doNotAutoCompressPictures"))
    anchor = next((child for child in root if isinstance(child.tag, str) and child.tag.startswith(f"{{{W_NS}}}")
                   and etree.QName(child).localname in SETTINGS_AFTER_NO_COMPRESSION), None)
    if anchor is not None:
        anchor.addprevious(element)
    else:
        root.append(element)
    files[name] = serialize(root)
    return True


def accept_all(root: etree._Element) -> None:
    """Terima semua Track Changes (semantik Accept All Word) langsung pada XML."""
    for row in list(root.iter(w("tr"))):
        properties = row.find(w("trPr"))
        if properties is None:
            continue
        if properties.find(w("del")) is not None:
            row.getparent().remove(row)
            continue
        for mark in properties.findall(w("ins")):
            properties.remove(mark)
    for table in list(root.iter(w("tbl"))):
        if not table.findall(w("tr")):
            table.getparent().remove(table)
    merged: list[etree._Element] = []
    for paragraph in list(root.iter(w("p"))):
        mark = paragraph.find(f"{w('pPr')}/{w('rPr')}")
        if mark is None:
            continue
        for element in mark.findall(w("ins")):
            mark.remove(element)
        if mark.find(w("del")) is not None:
            for element in mark.findall(w("del")):
                mark.remove(element)
            merged.append(paragraph)
        if not len(mark):
            properties = mark.getparent()
            properties.remove(mark)
            if not len(properties):
                paragraph.remove(properties)
    for element in list(root.iter(w("del"), w("moveFrom"))):
        parent = element.getparent()
        if parent is not None and parent.tag not in (w("rPr"), w("trPr")):
            parent.remove(element)
    for element in list(root.iter(w("ins"), w("moveTo"))):
        parent = element.getparent()
        if parent is None or parent.tag in (w("rPr"), w("trPr")):
            continue
        index = parent.index(element)
        for child in list(element):
            parent.insert(index, child)
            index += 1
        parent.remove(element)
    for change in list(root.iter(w("pPrChange"), w("rPrChange"), w("tblPrChange"), w("trPrChange"),
                                 w("tcPrChange"), w("sectPrChange"), w("tblGridChange"))):
        change.getparent().remove(change)
    for paragraph in merged:
        parent = paragraph.getparent()
        if parent is None:
            continue
        following = paragraph.getnext()
        content = [child for child in paragraph if child.tag != w("pPr")]
        if following is not None and following.tag == w("p"):
            properties = following.find(w("pPr"))
            position = 1 if properties is not None else 0
            for child in content:
                following.insert(position, child)
                position += 1
            parent.remove(paragraph)
        elif not "".join(paragraph.itertext()).strip() and not has_drawing(paragraph):
            parent.remove(paragraph)


def drop_comments(root: etree._Element, keep: set[int]) -> None:
    for element in list(root.iter(w("commentRangeStart"), w("commentRangeEnd"))):
        if int(element.get(w("id"), "-1")) not in keep:
            element.getparent().remove(element)
    for reference in list(root.iter(w("commentReference"))):
        if int(reference.get(w("id"), "-1")) in keep:
            continue
        run = reference.getparent()
        run.remove(reference)
        if not [child for child in run if child.tag != w("rPr")]:
            run.getparent().remove(run)


def clean_files(files: dict[str, bytes], comments_name: str, keep: set[int]) -> dict[str, bytes]:
    """Salinan bersih: semua perubahan diterima, hanya komentar yang masih butuh masukan penulis."""
    clean = dict(files)
    for part in TEXT_PARTS:
        if part in clean:
            root = etree.fromstring(clean[part])
            accept_all(root)
            drop_comments(root, keep)
            clean[part] = serialize(root)
    if comments_name in clean:
        comments = etree.fromstring(clean[comments_name])
        for comment in list(comments.iter(w("comment"))):
            if int(comment.get(w("id"), "-1")) not in keep:
                comments.remove(comment)
        clean[comments_name] = serialize(comments)
    return clean


def write_package(output: Path, infos: list[zipfile.ZipInfo], files: dict[str, bytes]) -> None:
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
                 if key in STRUCTURAL_KEYS or key in ("sebelum", "gaya", "tegak", "komentar_bersih", "pindah_pemisah_halaman", "lebar_cm")}
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
        attach_comment_to_block(created, "\n".join(notes), workspace.session, bool(revision.extra.get("komentar_bersih")))


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
        next_change_id=max_numeric_id(list(parts.values()), ("ins", "del", "moveFrom", "moveTo", "rPrChange", "pPrChange", "tblPrChange",
                                                                 "trPrChange", "tcPrChange", "sectPrChange")) + 1,
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
    if comment_root is not None:
        # Komentar yang sudah ada di naskah masukan milik penulis: selalu ikut ke salinan bersih.
        for comment in comment_root.iter(w("comment")):
            value = comment.get(w("id"), "")
            if value.lstrip("-").isdigit():
                session.clean_comment_ids.add(int(value))
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
    if config.get("pustaka"):
        library_path = Path(arguments.tata_tulis).resolve().parent / config["pustaka"]
        print("Sitasi Mendeley dan daftar pustaka APA 7:")
        for line in mendeley_citations(body, json.loads(library_path.read_text(encoding="utf-8")), styles, session):
            print(line)
    if arguments.tata_tulis:
        counts = apply_layout_rules(body, config, styles, session)
        print("Tata tulis: " + ", ".join(f"{name} {count}" for name, count in counts.items()))
    if arguments.nomori_ulang or config.get("nomori_ulang"):
        print("Penomoran ulang judul tabel/gambar (lama → baru):")
        for line in renumber_captions(body, styles, session, config):
            print(line)
    if config.get("daftar_per_bab"):
        print("Daftar gambar/tabel per bab:")
        for line in add_chapter_lists(body, styles, session):
            print(line)
    if config.get("seragamkan_tabel"):
        print("Format tabel diseragamkan:")
        for line in unify_tables(body, config, session):
            print(line)
    if arguments.uji:
        print("\nMode uji: tidak ada file yang ditulis.")
        return 0

    package.flush()
    if session.comments:
        files[comments_name] = build_comments_part(existing_comments, session.comments, session)
        ensure_comments_wiring(files)
    if config.get("perbarui_field") and set_update_fields(files):
        print("settings.xml: w:updateFields aktif (Word memperbarui daftar isi, tabel, gambar saat dibuka)")
    if config.get("jangan_kompres_gambar") and set_no_picture_compression(files):
        print("settings.xml: w:doNotAutoCompressPictures aktif (Word tidak mengompres gambar saat menyimpan)")

    write_package(output, infos, files)
    print(f"\nTersimpan: {output}")
    mode = "Track Changes" if session.tracked else "langsung (tanpa Track Changes)"
    print(f"Mode: {mode}; penulis revisi: {session.author}")
    if arguments.bersih:
        clean_output = Path(arguments.bersih)
        if clean_output.resolve() == source.resolve():
            sys.exit("File salinan bersih tidak boleh sama dengan file asli.")
        write_package(clean_output, infos, clean_files(files, comments_name, session.clean_comment_ids))
        print(f"Salinan bersih (semua perubahan diterima, {len(session.clean_comment_ids)} komentar): {clean_output}")
    if arguments.referensi_baru:
        new_works = [work for work in session.cited_works if work.new]
        prefix = Path(arguments.referensi_baru)
        prefix.with_suffix(".ris").write_text(export_ris(new_works), encoding="utf-8")
        prefix.with_suffix(".bib").write_text(export_bibtex(new_works), encoding="utf-8")
        print(f"Karya baru ({len(new_works)}): {prefix.with_suffix('.ris')} dan {prefix.with_suffix('.bib')}")
        fixed_works = sorted((work for work in session.cited_works if work.fixed and not work.new), key=reference_sort_key)
        fixes_path = prefix.parent / "koreksi-pustaka-mendeley.bib"
        fixes_path.write_text(export_bibtex(fixed_works, session.library_config), encoding="utf-8")
        print(f"Karya Mendeley yang dikoreksi ({len(fixed_works)}): {fixes_path}")
    return 0


CHAPTER_LIST_RE = re.compile(r'\\c\s+"((?:Tabel|Gambar) \d+\.)"')


def chapter_list_fields(body: etree._Element) -> list[tuple[str, etree._Element, etree._Element]]:
    """Field daftar gambar/tabel per bab (TOC \\c "Gambar 3."): (label, paragraf awal, paragraf akhir)."""
    found: list[tuple[str, etree._Element, etree._Element]] = []
    stack: list[list] = []
    for paragraph in body.iter(w("p")):
        for element in paragraph.iter(w("fldChar"), w("instrText")):
            if element.tag == w("instrText"):
                if stack:
                    stack[-1][0] += element.text or ""
                continue
            kind = element.get(w("fldCharType"))
            if kind == "begin":
                stack.append(["", paragraph])
            elif kind == "end" and stack:
                instruction, start = stack.pop()
                label = CHAPTER_LIST_RE.search(instruction)
                if instruction.strip().startswith("TOC") and label:
                    found.append((label.group(1), start, paragraph))
    return found


def add_chapter_lists(body: etree._Element, styles: StyleBook, session: Session) -> list[str]:
    """Tambahkan field TOC \\c "Gambar N." / "Tabel N." untuk bab yang judulnya belum punya daftar.

    Daftar Gambar/Tabel naskah memakai satu field per bab, sehingga judul bab baru (mis. Gambar 4.x)
    tidak pernah masuk daftar saat field diperbarui. Field baru diletakkan setelah daftar bab sebelumnya,
    berisi entri awal dengan PAGEREF ke bookmark pada judul; Word membangun ulang isinya saat diperbarui.
    """
    captions: dict[str, list[etree._Element]] = {}
    for paragraph in body_paragraphs(body):
        found = CAPTION_RE.match(live_text(paragraph))
        if found and seq_instruction(paragraph):
            captions.setdefault(f"{found.group(1)} {found.group(2)}.", []).append(paragraph)
    fields = chapter_list_fields(body)
    existing = {label for label, _, _ in fields}
    bookmark_ids = [int(item.get(w("id"))) for item in body.iter(w("bookmarkStart")) if (item.get(w("id")) or "").isdigit()]
    next_id = max(bookmark_ids, default=0) + 1
    lines = []
    for label in sorted(captions, key=lambda item: (item.split()[0], int(item.split()[1].rstrip(".")))):
        if label in existing:
            continue
        kind, chapter = label.split()[0], int(label.split()[1].rstrip("."))
        earlier = [field for field in fields if field[0].split()[0] == kind and int(field[0].split()[1].rstrip(".")) < chapter]
        if not earlier:
            lines.append(f"  {label}: tidak ada daftar {kind.lower()} bab sebelumnya sebagai acuan, dilewati")
            continue
        _, reference_start, reference_end = max(earlier, key=lambda field: int(field[0].split()[1].rstrip(".")))
        last_entry = reference_end.getprevious()
        if live_text(reference_end).strip() or last_entry is None or last_entry.tag != w("p"):
            lines.append(f"  {label}: daftar bab sebelumnya tidak diakhiri paragraf penutup kosong, dilewati")
            continue
        entry_properties = clean_properties(reference_start.find(w("pPr")))
        paragraphs = []
        for index, caption in enumerate(captions[label]):
            name = f"_Toc9{next_id:08d}"
            start = etree.Element(w("bookmarkStart"), {w("id"): str(next_id), w("name"): name})
            properties = caption.find(w("pPr"))
            (properties.addnext if properties is not None else lambda node: caption.insert(0, node))(start)
            caption.append(etree.Element(w("bookmarkEnd"), {w("id"): str(next_id)}))
            next_id += 1
            segments, _ = paragraph_model(caption)
            pieces: list[tuple[str, bool]] = []
            for segment in segments:
                if segment.kind == "text":
                    italic = styles.is_italic(segment.run, caption)
                    pieces.extend((char, italic) for char in segment.chars if char not in (FIELD, BARRIER))
            while pieces and pieces[0][0].isspace():
                pieces.pop(0)
            number = CAPTION_RE.match("".join(char for char, _ in pieces)).group(0)
            entry = etree.Element(w("p"))
            entry.append(deepcopy(entry_properties))
            plain = etree.Element(w("r"))
            if index == 0:
                opening = field_runs(plain, f' TOC \\h \\z \\c "{label}" ', [])[:3]
                opening[0].find(w("fldChar")).attrib.pop(w("fldLock"), None)
                entry.extend(opening)
            link = etree.SubElement(entry, w("hyperlink"), {w("anchor"): name, w("history"): "1"})
            bold = etree.Element(w("r"))
            etree.SubElement(etree.SubElement(bold, w("rPr")), w("b"))
            link.append(make_run(bold, list(number)))
            for chars, italic in merge_pieces(pieces[len(number):]):
                link.append(make_run(plain, list(chars), italic))
            tab = etree.SubElement(link, w("r"))
            etree.SubElement(tab, w("tab"))
            page = field_runs(plain, f" PAGEREF {name} \\h ", [])
            page[0].find(w("fldChar")).attrib.pop(w("fldLock"), None)
            link.extend(page)
            paragraphs.append(entry)
        for paragraph in paragraphs:
            mark_paragraph_inserted(paragraph, session)
        # Field bab sebelumnya ditutup di akhir entri terakhirnya (seperti antara daftar bab 2 dan 3);
        # paragraf penutup lama kini menutup field baru, jadi tidak ada baris kosong di antara daftar.
        closing_run = field_runs(etree.Element(w("r")), "", [])[-1]
        if session.tracked:
            wrapper = etree.SubElement(last_entry, w("ins"), revision_attributes(session))
            wrapper.append(closing_run)
        else:
            last_entry.append(closing_run)
        insert_elements(reference_end, paragraphs, session, before=True)
        fields.append((label, paragraphs[0], reference_end))
        lines.append(f"  {label}: daftar baru setelah daftar {kind.lower()} bab {int(label.split()[1].rstrip('.')) - 1} ({len(captions[label])} entri)")
    return lines


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
    parser.add_argument("--bersih", help="tulis juga salinan bersih .docx (semua perubahan diterima, hanya komentar "
                                         "bertanda komentar_bersih)")
    parser.add_argument("--referensi-baru", help="awalan berkas .ris/.bib untuk karya baru (belum di pustaka Mendeley)")
    parser.add_argument("--uji", action="store_true", help="cek saja tanpa menulis file")
    return run(parser.parse_args())


if __name__ == "__main__":
    sys.exit(main())
