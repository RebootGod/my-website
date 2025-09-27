Pada saat fixing atau develop fitur, selalu lakukan deep checking & validation pada apa yang dikerjakan, dan jadikan log.md, dbstructure.md, dbresult.md, functionresult.md sebagai referensi.
Lalu update log.md, dbstructure.md, dbresult.md, functionresult.md, setelah itu push ke git agar laravel forge bisa melakukan deployment ke production.
Gue lebih suka structure file yang professional.
Gue lebih suka kalo file untuk .php .js .css dipisah. Setiap css punya file nya sendiri, setiap php punya file nya sendiri, setial js punya file nya sendiri. Sehingga mudah untuk di debug.
Tidak ada local environment, cuman ada production saja. Jadi fixin harus langsung ke production