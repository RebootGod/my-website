# Rule and Instruction need to follow

- Gak ada Local Environment, hanya ada Production Environment
- Production Server di manage menggunakan Laravel Forge
- Pada saat fixing atau develop fitur, selalu lakukan deep checking & validation pada apa yang dikerjakan
- Selalu lakukan deep checking & validation, agar tidak terjadi error
- Jadikan log.md, dbstructure.md, dbresult.md, functionresult.md sebagai referensi
- Lalu update log.md, dbstructure.md, dbresult.md, functionresult.md
- Setelah itu push ke git agar laravel forge bisa melakukan deployment ke production
- Gue lebih suka structure file yang professional
- Gue lebih suka kalo file untuk .php .js .css dipisah. Setiap css punya file nya sendiri, setiap php punya file nya sendiri, setial js punya file nya sendiri. Sehingga mudah untuk di debug
- Gue lebih suka kalo setiap fitur, function, punya file nya tersendiri. Agar lebih mudah pada saat debug atau fixing
- Gue lebih suka kalo setiap fitur, function, punya file nya tersendiri, karena bisa dipakai untuk page lain kalo dibutuhkan. Jadi gaperlu bikin function baru, css baru, js baru, atau apapun yang baru


# You need to write "Aku sudah membaca workinginstruction.md dan mengerti (jelaskan apa yang lo ngerti)"