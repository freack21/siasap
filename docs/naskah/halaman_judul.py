"""Halaman cetak setiap judul Tabel/Gambar: halaman_judul.py naskah.docx naskah.pdf keluaran.json

Judul diambil dari .docx (paragraf yang memuat field SEQ), lalu dicari baris PDF yang memuat judul lengkapnya
di luar halaman daftar (baris daftar memakai titik-titik). Nomor halaman = baris terakhir halaman PDF.
"""
import json, re, subprocess, sys, zipfile
from lxml import etree
docx, pdf, out = sys.argv[1:4]
W = "{http://schemas.openxmlformats.org/wordprocessingml/2006/main}"
root = etree.fromstring(zipfile.ZipFile(docx).read("word/document.xml"))
titles = {}
for paragraph in root.iter(W + "p"):
    if not any((item.text or "").strip().startswith("SEQ") for item in paragraph.iter(W + "instrText")):
        continue
    text = " ".join("".join(item.text or "" for item in paragraph.iter(W + "t")).split())
    found = re.match(r"(Tabel|Gambar) (\d+\.\d+)\s", text)
    if found:
        titles[f"{found.group(1)} {found.group(2)}"] = text
squash = lambda value: re.sub(r"\s+", "", value)
pages = int(re.search(r"Pages:\s+(\d+)", subprocess.run(["pdfinfo", pdf], capture_output=True, text=True).stdout).group(1))
result = {}
for index in range(1, pages + 1):
    text = subprocess.run(["pdftotext", "-f", str(index), "-l", str(index), pdf, "-"], capture_output=True, text=True).stdout
    layout = subprocess.run(["pdftotext", "-layout", "-f", str(index), "-l", str(index), pdf, "-"], capture_output=True, text=True).stdout
    lines = [line.strip() for line in layout.splitlines() if line.strip()]
    if not lines or not re.fullmatch(r"\d+", lines[-1]) or sum(bool(re.search(r"\.{6,}\s*\d+$", line)) for line in lines) >= 3:
        continue
    flat = squash(text)
    for key, title in titles.items():
        if key not in result and squash(title)[:60] in flat:
            result[key] = lines[-1]
missing = sorted(set(titles) - set(result))
json.dump(result, open(out, "w"), ensure_ascii=False, indent=1)
print(len(result), "judul;", "tidak ketemu:", missing)
