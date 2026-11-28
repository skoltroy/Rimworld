import os
import xml.etree.ElementTree as ET

fields = {
    "label","description","labelShort","labelNoun","labelPlural","jobString",
    "reportString","letterLabel","letterText","text","summary","pawnLabel",
    "gerundLabel","textEnemy","textFriendly","textWillArrive","arrivedLetter",
    "baseInspectLine","tipText","customLabel","skillLabel","makingLabel",
    "verbNoun","labelMale","labelFemale","postfix","inspectString","ideoLabel"
}

def walk(e, path):
    li = 0
    for c in list(e):
        seg = c.tag
        if seg == "li":
            seg = str(li)
            li += 1
        np = path + [seg]
        kids = list(c)
        if kids:
            yield from walk(c, np)
        elif c.text and c.text.strip() and (seg in fields or e.tag == "rulesStrings"):
            yield ".".join(np), c.text.strip()

mods = ["3545374124","3628174105","2723801948"]
for mid in mods:
    r = f"D:/Games/RimWorld/Mods/{mid}/1.6/Defs"
    out = []
    seen = set()
    if os.path.isdir(r):
        for dp, dirs, fns in os.walk(r):
            for fn in fns:
                if not fn.endswith(".xml"):
                    continue
                p = os.path.join(dp, fn)
                try:
                    root = ET.parse(p).getroot()
                    for d in list(root):
                        if d.get("Abstract","").lower() == "true":
                            continue
                        dn = d.findtext("defName")
                        if not dn:
                            continue
                        dt = d.tag
                        for k, v in walk(d, []):
                            key = dn + "." + k
                            ident = (dt, key)
                            if ident not in seen:
                                seen.add(ident)
                                clean = v.replace("\r"," ").replace("\n"," ").replace("\t"," ")
                                out.append(f"{dt}\t{key}\t{clean}")
                except Exception as ex:
                    print(mid, "ERR", p, str(ex))
    op = f"G:/temp/rimworld-translations/{mid}-1.6.tsv"
    os.makedirs(os.path.dirname(op), exist_ok=True)
    with open(op, "w", encoding="utf-8", newline="") as fh:
        fh.write("\n".join(out))
    print(mid, len(out))
