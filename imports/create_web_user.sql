CREATE USER '${MYSQL_WEB_USER}'@'%' IDENTIFIED BY '${MYSQL_WEB_PASSWORD}';

GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_afrikaans_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_albanian_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_armenian_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_dutch_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_english_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_estonian_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_esu_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_french_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_georgian_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_german_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_greek_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_hebrew_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_hindi_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_italian_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_latvian_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_latin_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_lithuanian_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_polish_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_portuguese_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_romanian_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_russian_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_serbocroatian_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_spanish_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_swedish_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_tagalog_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_turkish_language TO '${MYSQL_WEB_USER}'@'%';
GRANT SELECT,INSERT,UPDATE ON lingwhaat.pronunciation_ukrainian_language TO '${MYSQL_WEB_USER}'@'%';

GRANT SELECT,INSERT,UPDATE ON lingwhaat.unique_pattern TO '${MYSQL_WEB_USER}'@'%';

FLUSH PRIVILEGES;