# Lemonade Admin CMS Auction

Volitelný administrační modul `cms.auction`. Composer manifest registruje modul, jeho migraci, oprávnění, audit a práci se sdílenými soubory. Veřejné routy ani šablony neposkytuje.

Kořen dražby obsahuje vlastníka, cenu v CZK, termín, doménový stav a externí URL. Jazykové mutace obsahují lokalitu, text, SEO, štítky a publikační stav. Canonical URL spravuje CMS core. Nový záznam vzniká jako koncept; smazání je měkké a obnovení vrací mutace do konceptu.

Po instalaci balíčku spusťte standardní migrace a synchronizaci oprávnění platformy. Oprávnění se automaticky nepřidělují rolím.
