RAC ADMINISTRACE V1
===================

Umístění:
    /administrace/

Databáze:
    rac

První instalace:
1. V hostingu / phpMyAdmin vytvoř databázi s názvem:
       rac

2. Do databáze rac importuj:
       administrace/database/rac.sql

3. Zkopíruj:
       administrace/config/db.example.php
   jako:
       administrace/config/db.local.php

4. V db.local.php doplň přístup k databázi rac.

5. Otevři v prohlížeči:
       /administrace/

6. Protože databáze zatím nemá žádného uživatele, systém automaticky přesměruje na:
       /administrace/setup.php

7. Vytvoř prvního superadmina.
   Jakmile existuje první účet, setup.php se automaticky zablokuje.

Role:
- superadmin
- admin

Oprávnění:
- superadmin může vytvářet adminy i další superadminy
- admin může vytvářet pouze adminy
- admin nemůže měnit nebo mazat superadmina
- nikdo nemůže smazat sám sebe
- nelze deaktivovat, degradovat ani smazat posledního aktivního superadmina

Bezpečnost:
- hesla se ukládají přes password_hash()
- formuláře mají CSRF tokeny
- login regeneruje session ID
- session cookie je HttpOnly a SameSite=Lax
- db.local.php je v .gitignore
- /config a /database mají zákaz přímého HTTP přístupu pro Apache 2.4+

Poznámka:
Tato V1 obsahuje pouze základ administrace uživatelů. Databázové tabulky pro
jazyky, stránky, překlady, produkty a objednávky budou přidány v dalších krocích.


VERZE V2 - SPRÁVA JAZYKŮ
========================
Pokud už máte V1 nainstalovanou:
1. Importujte do databáze rac:
       database/002_languages.sql

2. Nahrajte/aktualizujte:
       jazyky.php
       inc/leve-meny.class.php
       assets/css/admin.css

Modul Jazyky obsahuje:
- výchozí mutace cs, sk, pl, en, de
- přidání dalšího jazyka
- editaci kódu, názvu, vlastního názvu a locale
- aktivaci/deaktivaci
- pořadí jazyků
- nastavení výchozího jazyka
- bezpečné mazání
- výchozí jazyk nelze smazat ani deaktivovat

Poznámka:
Ve V2 ještě veřejný web čte seznam jazyků ze stávajícího PHP configu.
Následující krok bude napojení veřejného webu na tabulku languages.
