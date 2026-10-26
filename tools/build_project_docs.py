"""Build the project's Markdown manuals as searchable PDFs without app data.

Requires reportlab and TrueType fonts. Reads only the six named public manuals
and their local screenshots; never bootstraps Laravel or reads .env / databases.
"""

import argparse
import html
from pathlib import Path
import re

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER
from reportlab.lib.pagesizes import letter
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import inch
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    BaseDocTemplate, Flowable, Frame, Image, KeepTogether, LongTable, PageTemplate,
    Paragraph, Spacer, TableStyle,
)

MANUALS = (
    "PROYECTO_REQUISITOS", "MANUAL_USUARIO", "MANUAL_TECNICO",
    "DICCIONARIO_DATOS", "OPERACION_DESPLIEGUE", "VERIFICACION_PRUEBAS",
)


def inline(text):
    """Translate the deliberately small inline subset used in these manuals."""
    text = text.replace("—", "-").replace("–", "-").replace("\u2011", "-")
    parts = []
    token = re.compile(r"(`[^`]+`|\*\*[^*]+\*\*|\[[^\]]+\]\([^)]+\))")
    for i, part in enumerate(token.split(text)):
        if i % 2 == 0:
            parts.append(html.escape(part))
        elif part.startswith("`"):
            parts.append('<font name="Mono">' + html.escape(part[1:-1]) + '</font>')
        elif part.startswith("**"):
            parts.append("<b>" + html.escape(part[2:-2]) + "</b>")
        else:
            match = re.fullmatch(r"\[([^\]]+)\]\(([^)]+)\)", part)
            label, target = match.groups()
            target = target.replace(".md", ".pdf")
            if target.startswith(("https://", "http://", "mailto:")) or (
                target.endswith(".pdf") and not target.startswith("/")
            ):
                parts.append('<a color="#16486A" href="' + html.escape(target, quote=True)
                             + '">' + html.escape(label) + '</a>')
            else:
                parts.append(html.escape(label))
    return "".join(parts)


class ManualPDF(BaseDocTemplate):
    def __init__(self, filename, title, styles):
        super().__init__(str(filename), pagesize=letter,
                         leftMargin=0.65 * inch, rightMargin=0.65 * inch,
                         topMargin=0.72 * inch, bottomMargin=0.65 * inch,
                         title=title, author="Proyecto Alpha Fitness")
        self.manual_title = title
        self.styles = styles
        self.heading_count = 0
        frame = Frame(self.leftMargin, self.bottomMargin, self.width, self.height,
                      leftPadding=0, rightPadding=0, topPadding=0, bottomPadding=0)
        self.addPageTemplates(PageTemplate(id="manual", frames=frame, onPage=self.decorate))

    def decorate(self, canvas, doc):
        canvas.saveState()
        canvas.setFont("Body", 8)
        canvas.setFillColor(colors.black)
        canvas.drawString(self.leftMargin, 10.58 * inch, "ALPHA FITNESS")
        canvas.drawRightString(7.85 * inch, 10.58 * inch, "Edición 2.0  |  19 octubre 2026")
        canvas.drawString(self.leftMargin, 0.34 * inch, "Documentación del proyecto")
        canvas.drawRightString(7.85 * inch, 0.34 * inch, str(doc.page))
        canvas.restoreState()

    def afterFlowable(self, flowable):
        if isinstance(flowable, Paragraph) and flowable.style.name in ("Section", "Subsection"):
            self.heading_count += 1
            key = "section-" + str(self.heading_count)
            self.canv.bookmarkPage(key)
            level = 0 if flowable.style.name == "Section" else 1
            # Avoid an invalid level jump when a document begins with a subheading.
            if level and self.heading_count == 1:
                level = 0
            self.canv.addOutlineEntry(flowable.getPlainText(), key, level=level, closed=False)


class FigurePair(Flowable):
    """Place related phone screenshots side by side without table semantics."""
    def __init__(self, items, width, styles):
        super().__init__()
        self.width = width
        self.items = []
        for image, alt in items:
            caption = Paragraph(html.escape(alt) + ". Datos sintéticos de revisión.", styles["Caption"])
            caption_width, caption_height = caption.wrap(width / 2 - 16, 100)
            self.items.append((image, caption, caption_width, caption_height))
        self.height = max(img.drawHeight + ch + 16 for img, _, _, ch in self.items)

    def draw(self):
        for i, (img, caption, cw, ch) in enumerate(self.items):
            left = i * self.width / 2
            img.drawOn(self.canv, left + (self.width / 2 - img.drawWidth) / 2,
                       self.height - img.drawHeight)
            caption.drawOn(self.canv, left + 8, self.height - img.drawHeight - ch - 7)


def table_block(lines, width, styles):
    rows = [[cell.strip() for cell in row.strip().strip("|").split("|")]
            for row in lines if not re.fullmatch(r"[\s|:\-]+", row)]
    count = len(rows[0])
    if any(len(row) != count for row in rows):
        raise ValueError("Inconsistent Markdown table columns")
    # Give narrative columns more room than identifiers and status columns.
    if count == 2:
        ratios = [0.30, 0.70]
    elif count == 3:
        ratios = [0.12, 0.34, 0.54] if rows[0][0] == "ID" else [0.25, 0.28, 0.47]
    elif count == 4:
        ratios = [0.19, 0.21, 0.26, 0.34]
    elif count == 5:
        ratios = [0.42, 0.145, 0.145, 0.145, 0.145]
    else:
        ratios = [1 / count] * count
    content = [[Paragraph(inline(cell), styles["TableHead" if row_num == 0 else "Cell"])
                for cell in row] for row_num, row in enumerate(rows)]
    result = LongTable(content, colWidths=[width * x for x in ratios], repeatRows=1,
                       hAlign="LEFT", spaceBefore=7, spaceAfter=12)
    result.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#203A4B")),
        ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, colors.HexColor("#F1F5F7")]),
        ("GRID", (0, 0), (-1, -1), 0.5, colors.HexColor("#D9D9D9")),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("LEFTPADDING", (0, 0), (-1, -1), 8),
        ("RIGHTPADDING", (0, 0), (-1, -1), 8),
        ("TOPPADDING", (0, 0), (-1, -1), 6),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
    ]))
    return result


def make_styles():
    styles = getSampleStyleSheet()
    styles.add(ParagraphStyle("Prose", fontName="Body", fontSize=11, leading=15.2,
                              textColor=colors.black, spaceAfter=8, splitLongWords=True))
    styles.add(ParagraphStyle("DocTitle", parent=styles["Prose"], fontName="Body-Bold",
                              fontSize=24, leading=28, spaceAfter=18, keepWithNext=True))
    styles.add(ParagraphStyle("Section", parent=styles["Prose"], fontName="Body-Bold",
                              fontSize=15, leading=19, spaceBefore=16, spaceAfter=9,
                              keepWithNext=True))
    styles.add(ParagraphStyle("Subsection", parent=styles["Section"], fontSize=12,
                              leading=16, spaceBefore=12, spaceAfter=7))
    styles.add(ParagraphStyle("Cell", parent=styles["Prose"], fontSize=9.5,
                              leading=12.5, spaceAfter=0))
    styles.add(ParagraphStyle("TableHead", parent=styles["Cell"], fontName="Body-Bold",
                              textColor=colors.white))
    styles.add(ParagraphStyle("DocCode", parent=styles["Prose"], fontName="Mono",
                              fontSize=9, leading=12, leftIndent=7, spaceAfter=3))
    styles.add(ParagraphStyle("Caption", parent=styles["Prose"], fontSize=9,
                              leading=12, alignment=TA_CENTER, spaceAfter=12))
    styles.add(ParagraphStyle("ListItem", parent=styles["Prose"], leftIndent=15,
                              firstLineIndent=-11, spaceAfter=6))
    return styles


def build(source, destination, styles):
    lines = source.read_text(encoding="utf-8-sig").splitlines()
    title = lines[0].lstrip("# ").strip()
    doc = ManualPDF(destination, title, styles)
    story, paragraph = [], []

    def flush():
        if paragraph:
            story.append(Paragraph(inline(" ".join(paragraph)), styles["Prose"]))
            paragraph.clear()

    i = 0
    while i < len(lines):
        line = lines[i].strip()
        if not line:
            flush()
            i += 1
            continue
        if line.startswith("```"):
            flush()
            i += 1
            while i < len(lines) and not lines[i].strip().startswith("```"):
                code = lines[i].expandtabs(4)
                story.append(Paragraph(html.escape(code).replace(" ", "&#160;"), styles["DocCode"]))
                i += 1
            story.append(Spacer(1, 8))
        elif line.startswith("|"):
            flush()
            table_lines = []
            while i < len(lines) and lines[i].strip().startswith("|"):
                table_lines.append(lines[i])
                i += 1
            story.append(table_block(table_lines, doc.width, styles))
            continue
        elif re.fullmatch(r"!\[.*\]\(.*\)", line):
            flush()
            items = []
            while len(items) < 2:
                match = re.fullmatch(r"!\[(.*?)\]\((.*?)\)", lines[i].strip())
                alt, local = match.groups()
                image_path = (source.parent / local).resolve()
                image_path.relative_to(source.parent.resolve())
                image = Image(str(image_path))
                scale = min(2.65 * inch / image.imageWidth, 5.2 * inch / image.imageHeight)
                image.drawWidth = image.imageWidth * scale
                image.drawHeight = image.imageHeight * scale
                image.hAlign = "CENTER"
                items.append((image, alt))
                next_line = i + 1
                while next_line < len(lines) and not lines[next_line].strip():
                    next_line += 1
                if len(items) == 2 or next_line >= len(lines) or not re.fullmatch(r"!\[.*\]\(.*\)", lines[next_line].strip()):
                    break
                i = next_line
            if len(items) == 2:
                story.append(KeepTogether([Spacer(1, 8), FigurePair(items, doc.width, styles), Spacer(1, 12)]))
            else:
                image, alt = items[0]
                story.append(KeepTogether([Spacer(1, 8), image, Spacer(1, 7),
                                           Paragraph(html.escape(alt) + ". Datos sintéticos de revisión.", styles["Caption"])]))
        elif line.startswith("#"):
            flush()
            level = len(line) - len(line.lstrip("#"))
            heading = line[level:].strip()
            style = "DocTitle" if level == 1 else ("Section" if level == 2 else "Subsection")
            story.append(Paragraph(inline(heading), styles[style]))
        elif re.match(r"^(?:[-*] |\d+[.)] )", line):
            flush()
            match = re.match(r"^([-*]|\d+[.)]) (.*)", line)
            marker, body = match.groups()
            marker = "•" if marker in ("-", "*") else marker
            story.append(Paragraph(html.escape(marker) + "  " + inline(body), styles["ListItem"]))
        elif line in ("---", "***"):
            flush()
            story.append(Spacer(1, 9))
        else:
            paragraph.append(line.removeprefix("> "))
        i += 1
    flush()
    doc.build(story)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--source", type=Path, default=Path(__file__).resolve().parents[1] / "docs/documentacion")
    parser.add_argument("--output", type=Path)
    parser.add_argument("--font-dir", type=Path, default=Path("C:/Windows/Fonts"))
    args = parser.parse_args()
    output = args.output or args.source
    output.mkdir(parents=True, exist_ok=True)
    fonts = (("Body", "arial.ttf"), ("Body-Bold", "arialbd.ttf"),
             ("Body-Italic", "ariali.ttf"), ("Body-BoldItalic", "arialbi.ttf"),
             ("Mono", "consola.ttf"))
    for name, filename in fonts:
        pdfmetrics.registerFont(TTFont(name, str(args.font_dir / filename)))
    pdfmetrics.registerFontFamily("Body", normal="Body", bold="Body-Bold", italic="Body-Italic", boldItalic="Body-BoldItalic")
    styles = make_styles()
    for name in MANUALS:
        source = args.source / (name + ".md")
        if not source.is_file():
            raise FileNotFoundError(source)
        build(source, output / (name + ".pdf"), styles)
        print("Built " + name + ".pdf")


if __name__ == "__main__":
    main()
