# roberiodiogenes.com

Personal website for **Robério Diógenes**, independent Brazilian writer.  
Built with HTML, CSS, JavaScript (frontend) and PHP + MySQL (backend).

---

## About

This is the official website for the author Robério Diógenes, featuring:

- Author biography and portrait
- Featured book showcase (*O Jogo das Máscaras*)
- Newsletter subscription with email capture
- Unique visit counter
- Admin panel for managing subscribers
- Fully responsive design (mobile-first)
- Light, dark and high-contrast themes
- Accessibility controls (font size, theme switching)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3 (custom properties), Vanilla JS |
| Backend | PHP 8.1+ |
| Database | MySQL 5.7+ / MariaDB 10.4+ |
| Fonts | Google Fonts (Cinzel, Cormorant Garamond, EB Garamond) |
| Icons | Font Awesome 6 |
| Hosting | HostGator Brasil |

---

## Project Structure

```
roberiodiogenes.com/
│
├── index.html              # Main page
├── subscribe.php           # Newsletter subscription endpoint
├── visit.php               # Visit counter endpoint
├── .htaccess               # Apache config (security + cache)
│
├── config/
│   ├── db.php              # Database credentials (NOT committed)
│   └── setup.sql           # Database schema
│
├── admin/
│   └── index.php           # Admin panel (login + subscriber management)
│
└── img/
    ├── autor2.jpg          # Author portrait
    └── jogo-das-mascaras.jpg  # Book cover
```

---

## Local Development

See **LEIA-ME.md** for a detailed step-by-step guide to run this project
locally using XAMPP (in Portuguese).

**Quick start:**

1. Install [XAMPP](https://www.apachefriends.org/)
2. Copy project files to `C:\xampp\htdocs\roberiodiogenes\`
3. Create folder `img\` and copy the image files
4. Open phpMyAdmin → create database `roberiodiogenes` → run `config/setup.sql`
5. Copy `config/db.example.php` to `config/db.php` and fill in local credentials:
   - Host: `localhost`
   - Database: `roberiodiogenes`
   - User: `root`
   - Password: *(empty)*
6. Visit `http://localhost/roberiodiogenes/`

---

## Environment Configuration

This project uses `config/db.php` for database credentials.  
**This file is excluded from version control** (see `.gitignore`).

Copy the example file and configure for your environment:

```bash
cp config/db.example.php config/db.php
```

Then edit `config/db.php` with your credentials.

---

## Admin Panel

Access the admin panel at `/admin/` to:

- View and search newsletter subscribers
- Filter by active / unsubscribed status
- Export subscriber list as CSV
- View visit statistics

Default credentials (change immediately after first login):
- Username: `admin`
- Password: `RD@2025admin`

To generate a new bcrypt password hash:
```bash
php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT, ['cost'=>12]);"
```

---

## Deployment (HostGator)

1. Create MySQL database and user in cPanel
2. Run `config/setup.sql` via phpMyAdmin
3. Configure `config/db.php` with production credentials
4. Upload all files via FTP (FileZilla) to `public_html/`
5. Rename `htaccess.txt` to `.htaccess` on the server
6. Verify PHP version is 8.1+ in cPanel

See **GUIA_IMPLANTACAO.md** for the complete deployment guide (in Portuguese).

---

## Roadmap

- [x] Homepage (index.html)
- [x] Newsletter subscription (PHP + MySQL)
- [x] Visit counter
- [x] Admin panel
- [ ] Author page (`autor.html`)
- [ ] Library / Books page (`biblioteca.html`)
- [ ] Individual book page — *O Jogo das Máscaras*
- [ ] Blog / Diary (`diario.html`)
- [ ] Reader area (login + exclusive content)
- [ ] Email confirmation on subscription

---

## License

© 2025 Robério Diógenes. All rights reserved.  
This source code is private and not licensed for public use or redistribution.

---

*Made with care for storytelling and the written word.*
