import pdfplumber

with pdfplumber.open("Copy Dra. Fernanda (2).pdf") as pdf:
    for page in pdf.pages:
        text = page.extract_text()
        if text:
            print(text)