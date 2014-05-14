Programmi kasutamiseks peab arvutis leiduma kompileeritud kujul PHP programm (vähemalt versioon 5.3).
Seejärel tuleb avada käsurida, ning liikuda sellega PHP kausta, kus peaks asuma php.exe rakendus.
Samal ajal peaks mõne tekstiredaktori abil muutma add-frames.php lähtekoodi algust ning määrama ära konstandid
FRAMES_PATH, TEXT_PATH, OVERALL_PATH ning RETURN_PATH.
Esimene neist peab näitama täisteed freimide failini,
teine märgendatava tekstifailini,
kolmas üldteed, kus asuvad freimide lisaressursid  (freimide sünonüümide ning hüponüümide loendite failid),
ning viimane teed failini, kuhu soovitakse kirjutada analüüsitud teksti tulemus.
Meeles tuleb pidada, et tee ei tohi sisaldada täpitähti ning sisendina antud failid peavad olema UTF-8 kodeeringus. 
Seejärel tuleb käsureal sisestada käsk ”php.exe -f täistee add-frames.php failini” (näiteks C:\Kasutajad\Kasutaja\Töölaud\add-frames.php).