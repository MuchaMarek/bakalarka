Tento repozitár slúži na poksytnutie zdrojových kódov, ktoré patria k bakalárskej téme "Platforma na zber a vyhodnocovanie meraných údajov kvality ovzdušia s web aplikáciou".
Práca bola spravená na fakulte elektrotechniky a informatiky STU v Bratislave. 

Requirements:
-prístup k serveru, na ktorom vám bude bežať stránka, databáza a zber dát.
-server musí mať nainštalovaný Apache, alebo Nginx softvér
-server musí mať nainštalovaný MySQL, alebo MariaDB softvér

Steps for installation:
-Pre použitie aplikácie si musíte stiahnuť .zip súbor tohto repozitáru.  
-Nasledne si .zip extrahujete a uložíte na svoj server. 
-Musíte si vytvoriť config.php, do ktorého si nastavíte prihlasovacie údaje do svojej databázy.
-Na servery si musíte nastaviť CRON JOB, ktorý bude pravidelne spúštať kód parseData.php pre zber dát.
-V tomto bode by stránka mala fungovať

Modifikácia
-Pre pridavanie senzorov, z ktorých chcete ťahať dáta, si musíte najsť senzor ID na mape OpenSenseMap
-Vložte ho v súbore parseData.php do poľa $sensorIds.
-V tomto bode by mala aplikácia zbierať dáta z pridaného senzoru
