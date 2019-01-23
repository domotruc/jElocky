Présentation 
===

Le plugin jElocky interface les équipements de la marque [Elocky](https://elocky.com/) (serrure eVy, box eZy, beacon) avec Jeedom.
Il implémente l'[API mise à disposition par Elocky](https://elocky.com/fr/doc-api-test).

Les fonctionalités disponibles sont:


> **Note:**
> 
> Le plugin récupère les information du serveur Elocky, il n'a pas (encore) la capacité de les modifier. Donc la configuration des utilisateurs/lieux/objets doit continuer à se faire avec l'application mobile Elocky.

Configuration du plugin 
===

Après installation du plugin via le market, l’activer.

Renseigner le *Client ID* et le *Client secret*, préalablement sollicité auprès de Elocky, comme indiqué au début de la [documentation de l'API](https://elocky.com/fr/doc-api-test).


Utilisateurs
===

Ajout d'un utilisateur
---

Dans la page du plugin, accessible via *Plugins > Sécurité > jElocky*, commencer par connecter un nouvel utilisateur en cliquant sur le bouton pour se faire.

Renseigner le nom du nouvel utilisateur dans la boite de dialogue. Ce nom est propre au plugin jElocky.

La page de l'utilisateur s'affiche, renseigner son courriel et son mot de passe, activer le et sauvegarder. Après quelques secondes, le temps d'interroger les serveurs Elocky, les données de l'utilisateurs s'affichent ; les lieux et objets associés sont automatiquement ajoutés et la page est raffraichie si nécessaire.

> **Note:**
>
> Il n'est pour le moment pas possible de créer un nouvel utilisateur via le plugin : il faut l'avoir préalablement créé dans l'application mobile Elocky.

Données utilisateur et mise à jour
---

Les données de l'utilisateur sont Nom, Prénom, Téphone et Date de création.

Elles sont synchronisées avec le serveur Elocky à l'ouverture de la page utilisateur, ainsi que toutes les heures, à condition que l'utilisateur soit bien activé.

Suppression d'un utilisateur
---

Depuis la page du plugin, cliquer sur l'utilisateur à supprimer, puis sur le bouton *Supprimer* en haut à droite de l'interface.

Serons supprimés:

* L'utilisateur;
* Les lieux de l'utilisateur si ces derniers se retrouvent orphelins (i.e. si ils n'ont plus aucun autre utilisateur défini dans Jeedom);
* Les objets appartenant aux lieux supprimés.

> **Note:**
>
> Les éléments ne sont supprimés que sur Jeedom : rien n'est supprimé côté serveur Elocky.


Lieux
===

Les lieux sont automatiquement crées : lorsqu'un utilisateur est ajouté, ses lieux le sont également.

Dans la page du plugin, accessible via *Plugins > Sécurité > jElocky*, cliquer sur la carte d'un lieu pour afficher ses données.

Données de lieux et mise à jour
---

L'onglet **Informations** affiche les données d'adresse du lieu. Ces dernières sont synchronisées avec le serveur Elocky à l'ouverture de la page du lieu ainsi que toutes les heures, à condition que le lieu soit activé et qu'il appartienne à un utilisateur activé.

L'onglet **Commandes** affiche to

Objets
===

Mise à jour
---


API
===

Le plugin jElocky est accessible via l'[API HTTP](https://jeedom.github.io/core/fr_FR/api_http) du core de Jeedom.

Dans la suite de ce chapitre:

* `#IP_JEEDOM#` est l'url d’accès à Jeedom. Il s’agit (sauf si connection via le réseau local) de l’adresse internet permettant d'accéder à Jeedom depuis l’extérieur.
*  `#APIKEY#` est la clef API jElocky qui se trouve dans la page de *Configuration* de Jeedom, onglet *API*.

Test de l'API
---

Pour tester l'accès à l'API:

    http://#IP_JEEDOM#/core/api/jeeApi.php?apikey=#APIKEY#&type=jElocky&action=test

Retourne la date courante et OK si ca fonctionne.

Déclencher l'alarme d'un lieu
---

L'URL est:

    http://#IP_JEEDOM#/core/api/jeeApi.php?apikey=#APIKEY#&type=jElocky&action=trigger_alarm&id=#ID#

où #ID# est l'id du lieu pour lequel nous souhaitons déclencher l'alarme.

Cet API permet de récuperer l'évènement IFTTT de déclenchement de l'alarme d'un lieu. Pour celà, il faut créer:

* L'évènement IFTTT correspondant dans l'application mobile Elocky; et
* Une applet côté IFTTT du type:

    if Webhooks(Receive a web request) then Webhooks(Make a web request)

où:

* `Webhooks(Receive a web request)` reçoit l'évènement défini dans l'appli mobile jElocky;
* `Webhooks(Make a web request)` active l'URL objet de ce paragraphe.

FAQ
===

En cas d'erreur `{"error":"json_error","error_description":"Syntax error"}`: vérifier le le *Client iD* et *Client secret* dans la configuration du plugin.


<a id="changelog"></a>

Registre des évolutions
===


