<?php
/*
 * UITLOGGEN
 * Hoort bij: TE5
 * Uitleg: we wissen alles uit de sessie, zodat de gebruiker niet meer is ingelogd.
 */

require 'includes/functies.php';

$_SESSION = [];
session_regenerate_id(true);

zet_melding('succes', 'Je bent uitgelogd.');
ga_naar('login.php');
