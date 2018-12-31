Présentation 
===

A compléter.

Configuration du plugin 
===

A écrire.


Mise à jour
===

Toutes les minutes
---


API
===

L'URL de base commune à toutes les requêtes API est:
 
    http://#IP_JEEDOM#/plugins/jElocky/core/api/jeeElocky.php?apikey=#APIKEY#
    
\#APIKEY# est la clef API jElocky que l'on trouve dans la page de *Configuration* de Jeedom, onglet *API*.

Test de l'API
---

L'URL est:

`http://#IP_JEEDOM#/plugins/jElocky/core/api/jeeElocky.php?apikey=#APIKEY#&action=test`

Si tout est correctement configuré, cette URL retourne *OK*.

Déclencher l'alarme d'un lieu
---

L'URL est:

`http://#IP_JEEDOM#/plugins/jElocky/core/api/jeeElocky.php?apikey=#APIKEY#&action=trig_alarm&id=#ID#`

où #ID# est l'id du lieu.



FAQ
===

Vous aver l'erreur `{"error":"json_error","error_description":"Syntax error"}`: vérifier le le *Client iD* et *Client secret* dans la configuration du plugin.