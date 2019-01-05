Présentation 
===

Le plugin jElocky interface les équipements de la marque [Elocky](https://elocky.com/) (serrure eVy, box eZy, beacon) avec Jeedom
Il implémente l'[API mise à disposition par Elocky](https://elocky.com/fr/doc-api-test).

Configuration du plugin 
===

Après installation du plugin via le market, l’activer.

Renseigner le *Client ID* et le *Client secret*, préalablement sollicité auprès de Elocky, comme indiqué au début de la [documentation de l'API](https://elocky.com/fr/doc-api-test).


Utilisateurs
===

Dans la page du plugin, que nous trouvons via *Plugins > Sécurité > jElocky*, la première des cjoses à faire est de créer/connecter un nouvel utilisateur.

Cliquer 

Suppression
---



Mise à jour
---

Lieux
===

Mise à jour
---

Objets
===

Mise à jour
---


API
===

L'URL de base commune à toutes les requêtes API est:
 
    http://#IP_JEEDOM#/plugins/jElocky/core/api/jeeElocky.php?apikey=#APIKEY#
    
`#APIKEY#` est la clef API jElocky que l'on trouve dans la page de *Configuration* de Jeedom, onglet *API*.

Test de l'API
---

L'URL est:

    http://#IP_JEEDOM#/plugins/jElocky/core/api/jeeElocky.php?apikey=#APIKEY#&action=test

Retourne *OK* si la configuration est correcte.

Déclencher l'alarme d'un lieu
---

L'URL est:

`http://#IP_JEEDOM#/plugins/jElocky/core/api/jeeElocky.php?apikey=#APIKEY#&action=trig_alarm&id=#ID#`

où #ID# est l'id du lieu.



FAQ
===

En cas d'erreur `{"error":"json_error","error_description":"Syntax error"}`: vérifier le le *Client iD* et *Client secret* dans la configuration du plugin.

<a name=changelog></a>

# Registre des évolutions


