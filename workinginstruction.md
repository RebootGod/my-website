# Rule and Instruction need to follow

- Gak ada Local Environment, hanya ada Production Environment
- URL production di https://noobz.space
- Production Server di manage menggunakan Laravel Forge

- Pada saat fixing atau develop fitur, selalu lakukan deep checking & validation pada apa yang dikerjakan
- Selalu lakukan deep checking & validation, agar tidak terjadi error

- Pada saat fixing untuk movies, lakukan juga pengecekan untuk series. Begitu juga sebaliknya

- Setelah itu push ke git agar laravel forge bisa melakukan deployment ke production

- Gue lebih suka structure file yang professional
- Gue lebih suka kalo file untuk .php .js .css dipisah. Setiap css punya file nya sendiri, setiap php punya file nya sendiri, setial js punya file nya sendiri. Sehingga mudah untuk di debug
- Gue lebih suka kalo setiap fitur, function, punya file nya tersendiri. Agar lebih mudah pada saat debug atau fixing
- Gue lebih suka kalo setiap fitur, function, punya file nya tersendiri, karena bisa dipakai untuk page lain kalo dibutuhkan. Jadi gaperlu bikin function baru, css baru, js baru, atau apapun yang baru
- Gue lebih suka kalo setiap file memiliki maksimal 350 baris, buat file login_2 untuk lanjutin dan seterusnya, dan ubah nama file login menjadi login_1

- Gue lebih suka kalo setiap fiitur mempunyai file nya sendiir, contoh:
    Untuk fitur yang ada di menu Manage Movies pada Admin Panel, itu mempunyai nama yang seragam seperti manage_movies_admin_panel_....
    Contoh lain, seperti Edit Movies di menu Manage Movies pada Admin Panel, seperti Edit_Movies_Manage_Movies_Admin_Panel_,...
    Contoh lain, seperti Dashboard pada Admin Panel, mempunyai nama Dashboard_Admin_Panel_....


- Pastikan code aman dari SQL Injection, XSS, CSRF, IDOR, HTML Injection, dan common attacks berdasarkan OWASP top Ten 2024 dan OWASP top ten 2025


# You need to write "Aku sudah membaca workinginstruction.md dan mengerti (jelaskan apa yang lo ngerti)"