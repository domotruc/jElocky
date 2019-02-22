Présentation 
===

Le plugin **jElocky** interface les équipements de la marque [Elocky](https://elocky.com/) (serrure eVy, box eZy, beacon) avec Jeedom.
Il implémente l'[API mise à disposition par Elocky](https://elocky.com/fr/doc-api-test).

Les fonctionnalités disponibles sont:
* Intégration dans Jeedom des utilisateurs, lieux et objets tels que définis dans l'appli Elocky;
* Intégration des données associées, via des commandes information, afin de permettre leur utilisation dans l'eco-système Jeedom (scénario, ...);
* Fourniture d'une API pour récupérer, via IFTTT, l'information de déclenchement de l'alarme d'un lieu;
* Synchronisation périodique des données avec le serveur Elocky. 

> **Note:**
> 
> Le plugin récupère les information du serveur Elocky, il n'a pas (encore) la capacité de les modifier. Donc la configuration des utilisateurs/lieux/objets doit continuer à se faire avec l'application mobile Elocky.

![Capture écran](../images/jElocky_screenshot2.png){:height="554px" width="640px"}

Configuration du plugin 
===

Après installation du plugin via le market, l’activer.

Renseigner le *Client ID* et le *Client secret*, préalablement sollicité auprès de Elocky, comme indiqué au début de la [documentation de l'API](https://elocky.com/fr/doc-api-test).


Utilisateurs
===

Ajout d'un utilisateur
---

Dans la page du plugin, accessible via *Plugins > Sécurité > jElocky*, commencer par connecter un nouvel utilisateur en cliquant sur le bouton *Ajouter un utilisateur*.

Renseigner le nom du nouvel utilisateur dans la boite de dialogue. Ce nom est propre au plugin jElocky.

La page de l'utilisateur s'affiche, renseigner son courriel et son mot de passe, activer le et sauvegarder. Après quelques secondes, le temps d'interroger les serveurs Elocky, les données de l'utilisateurs s'affichent ; les lieux et objets associés sont automatiquement ajoutés et la page est raffraichie si nécessaire.

> **Note:**
>
> Il n'est pour le moment pas possible de créer un nouvel utilisateur via le plugin : il faut l'avoir préalablement créé dans l'application mobile Elocky.

<a id="synchro_user"></a>

Données utilisateur et mise à jour
---

Les données de l'utilisateur sont *Nom*, *Prénom*, *Téléphone* et *Date de création*.

Ces données, ainsi que les lieux et objets associés à l'utilisateur, sont synchronisées avec le serveur Elocky à l'ouverture de la page utilisateur, ainsi que toutes les heures, à condition que l'utilisateur soit bien activé.

Suppression d'un utilisateur
---

Depuis la page du plugin, cliquer sur l'utilisateur à supprimer, puis sur le bouton *Supprimer* en haut à droite de l'interface.

Serons supprimés:

* L'utilisateur;
* Les lieux de l'utilisateur si ces derniers se retrouvent orphelins (i.e. si ils n'ont plus aucun autre utilisateur défini dans Jeedom);
* Les objets appartenant aux lieux supprimés.

> **Note:**
>
> Les éléments ne sont supprimés que dans Jeedom : rien n'est supprimé sur les serveurs Elocky.

Lieux
===

Les lieux sont automatiquement crées : lorsqu'un utilisateur est ajouté, ou [ses données mises à jour](#synchro_user), ses lieux le sont également.

Dans la page du plugin, accessible via *Plugins > Sécurité > jElocky*, cliquer sur la carte d'un lieu pour afficher ses données.

<a id="synchro_lieux"></a>

Données de lieux et mise à jour
---

### Informations

L'onglet **Informations** affiche, en plus des données classiques d'un équipment Jeedom,  les données d'adresse du lieu. Ces dernières sont synchronisées avec le serveur Elocky à l'ouverture de la page du lieu, ainsi que toutes les heures, à condition que le lieu soit activé et qu'il soit lié à un utilisateur activé.

### Commandes

L'onglet **Commandes** affiche les commandes *info* d'un lieu, à savoir:

* *alarm*: indique si l'alarme d'un lieu est armée (0=désarmée, 1=armée);
* *alarm_triggered*: <a id="alarm_triggered"></a>indique si l'alarme d'un lieu vient d'être déclenchée (0=pas d'alarme, 1=alarme déclenchée). Elle sert à capturer l'évènement IFTTT de déclenchement de l'alarme d'un lieu. Elle ne monte que via l'appel de l'API [Déclencher l'alarme d'un lieu](#trigger_alarm), et est automatiquement remise à 0 par le plugin au démarrage de la minute qui suit son activation.

La commande *alarm* est mise à jour à l'ouverture de la page du lieu, ainsi que toutes les minutes, à condition que le lieu soit activé et qu'il soit lié à un utilisateur activé.

Suppression d'un lieu
---

Depuis la page du plugin, cliquer sur le lieu à supprimer, puis sur le bouton *Supprimer* en haut à droite de l'interface.

Serons supprimés:

* Le lieux;
* Les objets appartenant aux lieux supprimés.

> **Attention:**
>
> Les éléments ne sont pas supprimés côté serveur Elocky. Si vous ne le faites pas, ils seront rajoutés dans Jeedom à la prochaine [synchronisation des données de l'utilisateur](#synchro_user) associé. 


Objets
===

Les objets sont automatiquement crées à l'ajout ou à la mise à jour des lieux auquels ils sont associés.

Les objets supportés sont les cylindres électroniques eVy et les box eZy. Les beacons ne sont pas encore supportés.

Dans la page du plugin, accessible via *Plugins > Sécurité > jElocky*, cliquer sur la carte d'un objet pour afficher ses données.

Données d'objets et mise à jour
---

### Informations

L'onglet **Informations** affiche, en plus des données classiques d'un équipment Jeedom, la *Référence* du lieu. Cette dernière est synchronisée avec le serveur Elocky à l'ouverture de la page de l'objet, ainsi que toutes les heures, à condition que l'objet soit activé et qu'il soit associé à un lieu activé, lui-même lié à un utilisateur activé.


### Commandes

L'onglet **Commandes** affiche les commandes *info* d'un objet.

#### Pour un **cylindre électronique eVy** :

Nom   | Description | Valeurs
----- | -------| -------
battery (*) | Capacité restante de la pile (%) | Entier (0 à 100%)
connection | Connexion de l'objet | Entier
date_battery | Date d'installation de la batterie | Date (chaîne de caractères)
maj | Etat de la mise à jour de l'objet | Entier
nbAccess | Nombre d'accès | Entier
programme | Programme de l'objet | Entier
reveille | Degré de sensibilité | Entier
tension | Niveau de tension (V) | Nombre à virgule
veille | Etat de veille de l'objet | Entier
version | Version de l'objet | Chaîne de caractères

(*) La capacité restante de la pile est fournie au noyau de Jeedom ce qui permet de bénéficier des fonctionnalitées relatives aux batteries de ce dernier :

* Paramétrage des seuils d'alerte via la *Configuration avancée* de l'équipement, onglet *Alertes*;
* Affichage de la pile dans *Analyse > Equipements*.

#### Pour une **box eZy** :

Nom   | Description | Valeurs
----- | -------| -------
adresse_ip | Adresse IP publique | Chaîne de caractères
adresse_ip_local | Adresse IP sur son réesau local | Chaîne de caractères
connectionInternet | Etat connexion internet | Entier (0 ou 1 à confirmer)
nbObject | Nombre d'objets liés à la passerelle | Entier
state | Etat de la passerelle | Entier (0 ou 1 à confirmer)
vpn | Adresse du VPN | Chaîne de caractères

La mise à jour des informations se produit à l'ouverture de la page de l'objet, ainsi que toutes les minutes, à condition que l'objet soit activé et qu'il soit associé à un lieu activé, lui-même lié à un utilisateur activé.

Suppression d'un objet
---

Depuis la page du plugin, cliquer sur l'objet à supprimer, puis sur le bouton *Supprimer* en haut à droite de l'interface.

> **Attention:**
>
> L'objet n'est pas supprimé côté serveur Elocky. Si vous ne l'avez pas fait préalablement, il sera rajouté dans Jeedom à la prochaine [mise à jour des données du lieu](#synchro_lieux) associé (informations ou commandes).


API HTTP
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

<a id="trigger_alarm"></a>

Déclencher l'alarme d'un lieu
---

L'URL est:

    http://#IP_JEEDOM#/core/api/jeeApi.php?apikey=#APIKEY#&type=jElocky&action=trigger_alarm&id=#ID#

où #ID# est l'id du lieu pour lequel nous souhaitons déclencher l'alarme.

Cet API fait monter à la valeur 1 la commande info [*alarm_triggered*](#alarm_triggered). Elle permet de récuperer l'évènement IFTTT de déclenchement de l'alarme d'un lieu. Pour celà, il faut créer:

* L'évènement IFTTT correspondant dans l'application mobile Elocky; et
* Une applet côté IFTTT du type:

        if Webhooks(Receive a web request) then Webhooks(Make a web request)

où:

* `Webhooks(Receive a web request)` reçoit l'évènement défini dans l'appli mobile jElocky;
* `Webhooks(Make a web request)` active l'URL objet de ce paragraphe.

FAQ
===

### Erreur dans les paramètres de connexion 
En cas d'erreur `{"error":"json_error","error_description":"Syntax error"}`: vérifier *Client iD* et *Client secret* dans la configuration du plugin.


<a id="changelog"></a>

Registre des évolutions
===

#### 22/02/2019 (beta)

Correction [#2](https://github.com/domotruc/jElocky/issues/2): status armement alarme non récupéré


#### 31/01/2019 (beta)

1ère version du plugin, en beta uniquement.


