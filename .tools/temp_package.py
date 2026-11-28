import os
import shutil
import zipfile
import hashlib
import xml.etree.ElementTree as ET
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

MODS = Path("D:/Games/RimWorld/Mods")
WORK = Path("G:/GIT/ionos-web/skoltroy/rimworld")
EXPORT = WORK / ".tools/exports"
BACKUPS = WORK / "extensions"
TEMP = Path("G:/temp/rimworld-packages")
TEMP.mkdir(parents=True, exist_ok=True)

ids = {
    "3545374124": "GravTech",
    "3628174105": "GravTech - Big cannons",
    "2723801948": "Vanilla Factions Expanded - Pirates",
}

def hash_file(p):
    h = hashlib.sha256()
    with open(p, "rb") as f:
        for chunk in iter(lambda: f.read(1024 * 1024), b""):
            h.update(chunk)
    return h.hexdigest()

def font(size):
    candidates = [
        r"C:/Windows/Fonts/arialbd.ttf",
        r"C:/Windows/Fonts/segoeui.ttf",
    ]
    for c in candidates:
        if os.path.exists(c):
            return ImageFont.truetype(c, size)
    return ImageFont.load_default()

def make_preview(src, dst):
    if src.exists():
        im = Image.open(src).convert("RGB")
        im.thumbnail((640, 360), Image.Resampling.LANCZOS)
        canvas = Image.new("RGB", (640, 360), (128,128,128))
        canvas.paste(im, ((640-im.width)//2, (360-im.height)//2))
    else:
        canvas = Image.new("RGB", (640, 360), (128,128,128))
    draw = ImageDraw.Draw(canvas)
    # French tricolor in top-right
    x0, y0, w, h = 548, 10, 82, 50
    bw = w // 3
    draw.rectangle((x0,y0,x0+bw,y0+h), fill=(0,85,164))
    draw.rectangle((x0+bw,y0,x0+bw*2,y0+h), fill=(255,255,255))
    draw.rectangle((x0+bw*2,y0,x0+w,y0+h), fill=(239,65,53))
    draw.rectangle((x0-2,y0-2,x0+w+2,y0+h+2), outline=(20,20,20), width=2)
    # Credit panel in top-left
    panel = (10,10,330,58)
    draw.rectangle(panel, fill=(245,245,245), outline=(20,20,20), width=3)
    draw.text((20,22), "French Trad by Skoltroy", font=font(22), fill=(20,20,20), stroke_width=1, stroke_fill=(20,20,20))
    canvas.save(dst, "PNG")

for mid, display in ids.items():
    src = MODS / mid
    live = MODS / f"{mid} - French TradFix"
    if live.exists():
        shutil.rmtree(live)
    live.mkdir(parents=True)

    # Package metadata
    about_src = src / "About/About.xml"
    about_live = live / "About"
    about_live.mkdir()
    ET.register_namespace("", "")
    tree = ET.parse(about_src)
    root = tree.getroot()
    name = root.find("name")
    if name is None:
        name = ET.SubElement(root, "name")
    name.text = display + " - French TradFix"
    url = root.find("url")
    if url is None:
        url = ET.SubElement(root, "url")
    url.text = "https://rimworld.skoltroy.com"
    desc = root.find("description")
    if desc is not None:
        desc.text = (desc.text or "").strip() + f"\n\nSource Workshop: https://steamcommunity.com/sharedfiles/filedetails/?id={mid}"
    tree.write(about_live / "About.xml", encoding="utf-8", xml_declaration=True)

    lf = src / "LoadFolders.xml"
    if lf.exists():
        shutil.copy2(lf, live / "LoadFolders.xml")

    fr_src = EXPORT / mid / "Languages/French"
    if fr_src.exists():
        shutil.copytree(fr_src, live / "Languages/French")

    if mid == "2723801948":
        names_src = Path("G:/temp/rimworld-translations/2723801948-Names-Fr")
        names_dst = live / "Languages/French/Strings/Names"
        names_dst.mkdir(parents=True, exist_ok=True)
        for p in names_src.glob("*"):
            shutil.copy2(p, names_dst / p.name)
        fr_names_exp = EXPORT / mid / "Languages/French/Strings/Names"
        fr_names_exp.mkdir(parents=True, exist_ok=True)
        for p in names_src.glob("*"):
            shutil.copy2(p, fr_names_exp / p.name)

    preview_src = src / "About/preview.png"
    if not preview_src.exists():
        preview_src = src / "About/Preview.png"
    make_preview(preview_src, about_live / "Preview.png")

    # Stage ZIP in temp with package-root paths
    stage = TEMP / f"{mid}-stage"
    if stage.exists():
        shutil.rmtree(stage)
    shutil.copytree(live, stage)
    zip_path = BACKUPS / f"{mid} - French TradFix.zip"
    tmp_zip = BACKUPS / f".{mid}-French-TradFix.tmp.zip"
    if tmp_zip.exists():
        tmp_zip.unlink()
    with zipfile.ZipFile(tmp_zip, "w", zipfile.ZIP_DEFLATED) as z:
        for p in sorted(stage.rglob("*")):
            if p.is_file():
                z.write(p, p.relative_to(stage).as_posix())

    # Validate archive XML and live/ZIP parity
    with zipfile.ZipFile(tmp_zip, "r") as z:
        bad = z.testzip()
        if bad:
            raise RuntimeError(f"ZIP CRC failure: {bad}")
        names = set(z.namelist())
        stage_names = {p.relative_to(stage).as_posix() for p in stage.rglob("*") if p.is_file()}
        if names != stage_names:
            raise RuntimeError(f"ZIP path mismatch for {mid}")
        for n in names:
            if n.lower().endswith(".xml"):
                ET.fromstring(z.read(n))
    if zip_path.exists():
        zip_path.unlink()
    tmp_zip.rename(zip_path)

    print(mid, "live=", live, "zip=", zip_path, "files=", len([p for p in live.rglob("*") if p.is_file()]))
