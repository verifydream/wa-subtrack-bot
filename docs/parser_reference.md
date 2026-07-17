# Parser Reference Table

This table illustrates how the natural language parsing logic interprets various user input patterns into structured subscription data.

| Input Pattern | Parsed Service Name | Parsed Amount | Currency | Parsed Billing Day |
|---------------|---------------------|---------------|----------|--------------------|
| `Netflix 149 rb tanggal 15` | Netflix | 149000 | IDR | 15 |
| `Hosting digitalocean 15 dollar tiap tanggal 1` | Hosting Digitalocean | 15 | USD | 1 |
| `Spotify premium 55000 tanggal 20` | Spotify Premium | 55000 | IDR | 20 |
| `VPS 1.5jt tanggal 1` | Vps | 1500000 | IDR | 1 |
| `Google One 269000 tgl 2` | Google One | 269000 | IDR | 2 |
| `Hetzner 5 euro setiap tanggal 5` | Hetzner | 5 | EUR | 5 |
| `ChatGPT 20 usd tanggal 12` | Chatgpt | 20 | USD | 12 |
| `AWS $150 tgl 30` | Aws | 150 | USD | 30 |
| `Canva 75 k tanggal 8` | Canva | 75000 | IDR | 8 |
| `Rp 150000 iCloud tgl 10` | Icloud | 150000 | IDR | 10 |
