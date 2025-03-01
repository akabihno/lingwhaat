# LingWhaat?

This project aims to detect the language of written text with high speed and accuracy.

Initial IPA data for universal transliteration was parsed from [Wiktionary](https://en.wiktionary.org/wiki/Wiktionary:Main_Page) [dumps](https://dumps.wikimedia.org/enwiktionary/latest/) (specifically: enwiktionary-latest-pages-articles-multistream.xml.bz2)\
List of all articles IPA data has been taken from:
* Latvian:
[а/ā](docs/Latvian/en_wiktionary_latvian_a.md),
[b](docs/Latvian/en_wiktionary_latvian_b.md),
[c/č](docs/Latvian/en_wiktionary_latvian_c.md),
[d](docs/Latvian/en_wiktionary_latvian_d.md),
[e/ē](docs/Latvian/en_wiktionary_latvian_e.md),
[f](docs/Latvian/en_wiktionary_latvian_f.md),
[g/ģ](docs/Latvian/en_wiktionary_latvian_g.md),
[h](docs/Latvian/en_wiktionary_latvian_h.md),
[i/ī](docs/Latvian/en_wiktionary_latvian_i.md),
[j](docs/Latvian/en_wiktionary_latvian_j.md),
[k/ķ](docs/Latvian/en_wiktionary_latvian_k.md),
[l/ļ](docs/Latvian/en_wiktionary_latvian_l.md),
[m](docs/Latvian/en_wiktionary_latvian_m.md),
[n/ņ](docs/Latvian/en_wiktionary_latvian_n.md),
[o](docs/Latvian/en_wiktionary_latvian_o.md),
[p](docs/Latvian/en_wiktionary_latvian_p.md),
[r](docs/Latvian/en_wiktionary_latvian_r.md),
[s/š](docs/Latvian/en_wiktionary_latvian_s.md),
[t](docs/Latvian/en_wiktionary_latvian_t.md),
[u/ū](docs/Latvian/en_wiktionary_latvian_u.md),
[v](docs/Latvian/en_wiktionary_latvian_v.md),
[z/ž](docs/Latvian/en_wiktionary_latvian_z.md)
* Polish:
[а/ą](docs/Polish/en_wiktionary_polish_a.md),
[b](docs/Polish/en_wiktionary_polish_b.md),
[c/ć](docs/Polish/en_wiktionary_polish_c.md),
[d](docs/Polish/en_wiktionary_polish_d.md),
[e/ę](docs/Polish/en_wiktionary_polish_e.md),
[f](docs/Polish/en_wiktionary_polish_f.md),
[g](docs/Polish/en_wiktionary_polish_g.md),
[h](docs/Polish/en_wiktionary_polish_h.md),
[i](docs/Polish/en_wiktionary_polish_i.md),
[j](docs/Polish/en_wiktionary_polish_j.md),
[k](docs/Polish/en_wiktionary_polish_k.md),
[l/ł](docs/Polish/en_wiktionary_polish_l.md),
[m](docs/Polish/en_wiktionary_polish_m.md),
[n](docs/Polish/en_wiktionary_polish_n.md),
[o/ó](docs/Polish/en_wiktionary_polish_o.md),
[p](docs/Polish/en_wiktionary_polish_p.md),
[q](docs/Polish/en_wiktionary_polish_q.md),
[r](docs/Polish/en_wiktionary_polish_r.md),
[s/ś](docs/Polish/en_wiktionary_polish_s.md),
[t](docs/Polish/en_wiktionary_polish_t.md),
[u](docs/Polish/en_wiktionary_polish_u.md),
[v](docs/Polish/en_wiktionary_polish_v.md),
[w](docs/Polish/en_wiktionary_polish_w.md),
[x](docs/Polish/en_wiktionary_polish_x.md),
[y](docs/Polish/en_wiktionary_polish_y.md),
[z/ź/ż](docs/Polish/en_wiktionary_polish_z.md)
* Russian:
[а](docs/Russian/en_wiktionary_russian_а.md), 
[б](docs/Russian/en_wiktionary_russian_б.md),
[в](docs/Russian/en_wiktionary_russian_в.md),
[г](docs/Russian/en_wiktionary_russian_г.md),
[д](docs/Russian/en_wiktionary_russian_д.md),
[е](docs/Russian/en_wiktionary_russian_е.md),
[ж](docs/Russian/en_wiktionary_russian_ж.md),
[з](docs/Russian/en_wiktionary_russian_з.md),
[и](docs/Russian/en_wiktionary_russian_и.md),
[й](docs/Russian/en_wiktionary_russian_й.md),
[к](docs/Russian/en_wiktionary_russian_к.md),
[л](docs/Russian/en_wiktionary_russian_л.md),
[м](docs/Russian/en_wiktionary_russian_м.md),
[н](docs/Russian/en_wiktionary_russian_н.md),
[о](docs/Russian/en_wiktionary_russian_о.md),
[п](docs/Russian/en_wiktionary_russian_п.md),
[р](docs/Russian/en_wiktionary_russian_р.md),
[с](docs/Russian/en_wiktionary_russian_с.md),
[т](docs/Russian/en_wiktionary_russian_т.md),
[у](docs/Russian/en_wiktionary_russian_у.md),
[ф](docs/Russian/en_wiktionary_russian_ф.md),
[х](docs/Russian/en_wiktionary_russian_х.md),
[ц](docs/Russian/en_wiktionary_russian_ц.md),
[ч](docs/Russian/en_wiktionary_russian_ч.md),
[ш](docs/Russian/en_wiktionary_russian_ш.md),
[щ](docs/Russian/en_wiktionary_russian_щ.md),
[ы](docs/Russian/en_wiktionary_russian_ы.md),
[э](docs/Russian/en_wiktionary_russian_э.md),
[ю](docs/Russian/en_wiktionary_russian_ю.md),
[я](docs/Russian/en_wiktionary_russian_я.md)

## Requirements
* docker

## Start

```bash
cp env.dist .env #adjust the values for your needs

docker compose up -d

docker exec -i database mysql --default-character-set=utf8mb4 --force -u root -ppassword -P 3327 lingwhaat < imports/import.sql
```

Database listens on port 3327 by default - configurable in my.cnf