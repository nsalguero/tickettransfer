# Tickettransfer
Ce plugin a pour objet de permettre à un technicien de transférer un ticket de façon plus simple que ce que permet nativement de faire GLPI. 

Il se présente sous la forme d'un onglet proposant soit de requalifier le ticket (changer l'entité, le type ou la catégorie), soit de l'escalader (le transférer à un autre groupe). 

Il recouvre certaines fonctionnalités fournies par le plugin 'Escalade', en les présentant autrement, et avec des réglages différents. Je détaillerai plus loin.

## La requalification
Par requalification j'entends tout ce qui consiste à re-catégoriser le ticket. Ce plugin fournit les possibilités suivantes:
* Changer l'entité du ticket. Dans ce cas, le technicien qui effectue le transfert doit choisir une catégorie qui existe dans l'entité de destination. Ce choix diffère du transfert natif de GLPI, qui lorsqu'on change un ticket d'entité, recopie la catégorie d'origine dans l'entité de destination, ce qui n'est pas nécessairement ce qu'on souhaite.
* Changer le type et/ou la catégorie
* Réattribuer le ticket au groupe technique de la catégorie de destination. Cette fonctionnalité ressemble à une des options du plugin Escalade, cf. plus bas. (À noter, si le groupe change, le comportement en détail est le même que celui décrit dans le paragraphe escalade)
* Il est possible au technicien lors du transfert de demander à être placé en observateur du ticket. Concrètement, il n'y a aucune différence avec le fait de se mettre en observateur via l'interface native de GLPI. L'intérêt est que c'est fait dans la même action, et qu'on peut régler un comportement par défaut de cette option pour ne pas avoir à y penser à chaque fois.
* Il est possible de justifier ce transfert par un message libre. En pratique, ce message sera ajouté au ticket en tant que suivi public (avec un préfixe pour identifier qu'il s'agit d'une justification de transfert). L'administrateur peut, s'il le souhaite, forcer les techniciens à justifier leurs transferts.

Une notification peut être envoyée à l'issue.

## L'escalade
Par escalade j'entends tout simplement un transfert vers un autre groupe.
Ce plugin fournit les possibilités suivantes:
* Choisir un autre groupe (parmi les groupes auquel peut être attribué un ticket, et accessibles depuis l'entité du ticket). Les plugins limitant les groupes auxquels on peut transférer le ticket ne sont pas pris en compte dans cette version.
* Demander à être placé en observateur (idem requalification)
* Justifier le transfert (idem requalification)

Une notification peut être envoyée à l'issue.

A noter, lorsque le ticket est escaladé, le groupe technique est remplacé par le groupe choisi.
Le plugin est prévu pour fonctionner avec un ticket systématiquement attribué à un unique groupe.
Si un ticket qui était attribué à plusieurs groupes est transféré, tous les groupes existants sont supprimés, et le groupe de destination ajouté.
En aucun cas Tickettransfer ne permet d'attribuer un ticket à plusieurs groupes.
De plus, lors d'un transfert, tous les techniciens auxquels était attribué le ticket sont retiré, sauf ceux qui font aussi partie du groupe de destination.

## Les notifications
Ce qui connaissent bien les rouages de GLPI auront remarqué que chaque transfert tel que pratiqué par ce plugin déclenche tout un tas d'actions en interne à GLPI, chacune pouvant entrainer une notification.
C'est ce qui m'est arrivé pendant mes tests, au premier transfert j'ai reçu 3-4 mails d'un coup.  
Pour corriger ça, le plugin désactive toutes les notifications associées aux actions 'de base' qu'il fait en interne, et les remplace par une unique notification, qui a lieu à la fin du transfert.
On distingue deux évènements : un pour la requalification, et un pour l'escalade.

Dans les deux cas, trois données dont ajoutées pour les modèles de notification :
* ```##ticket.tickettransfer.author##```, qui donne le nom du technicien ayant fait le transfert
* ```##ticket.tickettransfer.message##```, qui donne le message de transfert (donc le dernier suivi, moins le préfixe ajouté au suivi)
* ```##ticket.tickettransfer.groupchanged##```, qui est vrai ssi le groupe technique auquel est attribué le ticket a changé (toujours vrai lors d'une escalade, mais plus intéressant lors d'une requalification)

Note : ce comportement permet de corriger un petit défaut que j'ai constaté lorsque j'utilise à la fois le plugin Escalade (pour son option supprimer le groupe précédent lorsque j'ajoute un groupe technique), et Comportement (pour la notification supplémentaire en cas d'ajout de groupe technique) : en pratique, le nouveau groupe est ajouté avant la suppression de l'ancien, ce qui fait qu'au niveau de la notification, il n'est pas possible de différencier l'ancien du nouveau groupe, donc on ne peut pas leur envoyer des mails différents.

## Comparaison avec le plugin escalade
Ce plugin apporte des fonctionnalités similaires à des options du plugin Escalade, mais pas parfaitement identiques.
Il propose aussi un comportement qui n'est pas compatible avec certaines options d'Escalade (par pas compatible, ce n'est pas que je m’attends à des problèmes particuliers, c'est juste que le comportement ne sera pas dans la logique de ce que propose Tickettransfer.
Après, à vous de tester plus en détail et de juger si ça vous convient).

Il est recommandé de désactiver les fonctionnalités correspondantes d'Escalade si on utilise Tickettransfer.
Par contre, les autres fonctionnalités d'escalade (dont l'historique des groupes, que je trouve personnellement très agréable) fonctionnent très bien avec Tickettransfer.

Fonctionnalité par fonctionnalité :
* Supprimer les anciens groupes lors de l'ajout d'un nouveau => cette fonctionnalité est fournie à l'identique par le plugin Tickettransfer (à ceci près que le plugin Escalade propose de la désactiver, alors que Tickettransfer la rend obligatoire)
* Suppression des techniciens lors d'une escalade => Lors d'un transfert, Tickettransfer supprime les techniciens auxquels était attribué le ticket, sauf s'ils font aussi partie du groupe de destination. (Là aussi, il ne s'agit pas d'un comportement désactivable, contrairement à Escalade)
* Assigner le groupe technique lors d'un changement de catégories => Tickettransfer propose de le faire au technicien qui fait le transfert, mais sans le forcer. Cela permet à celui-ci de juger si c'est pertinent ou non. L'autre grosse différence avec Escalade, c'est que cette attribution au groupe technique associé à la catégorie ne se fait que lors du transfert, ce qui corrige selon moi un gros défaut d'Escalade (cf. plus bas)
* Prendre le groupe du technicien > à la modification => pas comparable, et pas compatible
* Assigner le responsable technique lors d'un changement de catégories => pas comparable, pas compatible
* Activer le filtrage sur les groupes d'attribution => je n'ai pas testé, mais probablement pas pris en compte par Tickettransfer (note : pareil pour les autres plugins qui limitent les groupes accessibles, Tickettransfer les ignore)

Une autre grande différence avec Escalade est que ce plugin agit uniquement via l'ajout d'un nouvel onglet sur le ticket, alors que le plugin Escalade fonctionne en se branchant sur les évènements de GLPI.
Pour faire simple, ça a pour avantage que les modifications faites par Tickettransfer ne se font que quand on passe par cet onglet; et pour inconvénient exactement la même chose, à savoir que si on n'utilise pas l'onglet dédié, on ne bénéficie pas des fonctionnalités du plugin.

Exemple pratique, l'attribution du ticket au groupe associé à une catégorie :
* avec Escalade, dès qu'on change la catégorie le ticket est réattribué. Jusque-là, très bien. Mais admettons que le ticket soit ensuite escaladé vers un autre groupe. Si ce groupe apporte la moindre modification dans la partie haute du ticket, Escalade re-règle le groupe technique à celui associé au ticket. Selon l'organisation qu'on souhaite mettre en place, ça peut être un comportement extrêmement gênant.
* avec Tickettransfer, si on change la catégorie via le choix fourni par GLPI natif, rien ne se passe (on peut même le faire sans apporter de justification au transfert alors que le plugin a été paramétré pour que celle-ci soit obligatoire). C'est parce qu'il faut impérativement passer par l'onglet dédié: en passant par celui-ci, le groupe est bien réattribué comme on le souhaite (donc jusque-là c'est plutôt pas terrible, car sujet à erreur et contournement). Par contre, si on rejoue le scénario décrit plus haut, cad qu'on escalade le ticket, on n'aura plus ce problème de désescalade involontaire qu'on rencontre avec Escalade. De plus, un technicien du niveau 2 pourra même s'il le juge nécessaire changer la catégorie du ticket sans le réaffecter.
Bref, Tickettransfer permet plus de finesse dans les actions, mais est facilement contournable en utilisant les champs nativement mis à disposition par GLPI.  

Ma solution contre ça : forcer le technicien à passer par l'onglet dédié en utilisant uihacks, et spécifiquement sa fonctionnalité permettant de désactiver des champs dans l'interface utilisateur. La règle suivante devrait faire l'affaire :
* profils => les profils à brider (a priori tous, sauf éventuellement admin)
* url cibles => ```@^ticket\.form\.php\?id=\d+@```
* selecteur => ```select[name="type"], select[name="itilcategories_id"], select[name="_itil_assign[_type]"] option[value="group"]```
* infobulle => Pour transférer un ticket, passez par l'onglet Tickettransfer
* désactiver => oui

## La configuration dans le détail
Les paramètres sont les suivants:
* Autoriser la requalification => oui/non, surchargeable par profil
* Autoriser l'escalade => oui/non, surchargeable par profil
* Notification lors d'une requalification => oui/non/seulement si le groupe assigné change
* Notification lors d'une escalade => oui/non/seulement si le technicien qui fait le transfert n'est pas de le groupe de destination
* Entités vers lesquelles le transfert est autorisé => le choix des d’entités vers lesquelles le technicien peut transférer un ticket. C'est une liste en dur, qui n'est pas liée aux entités accessibles à l'utilisateur, mais personnalisable par profil. Ça a été fait pour répondre à un besoin précis lié à une utilisation précise de GLPI (et un peu atypique, il fallait pouvoir transférer vers une entité à laquelle on n'a pas accès avec ce profil). Rétrospectivement, ce n’est pas un très bon choix, je ferai probablement une amélioration sur ce point si quelqu'un manifeste de l'intérêt pour ce plugin. Par exemple, ça pourrait être entités accessible + liste en dur d'entité supplémentaires + liste en dur d'entité interdites même si accessibles.
* Forcer l'utilisateur à justifier les transferts => oui/non, surchargeable par profil
* Préfixe pour la justification des transferts => surchargeable par profil. C'est le message ajouté dans le suivi avant la justification. Il vise à différencier les suivis 'classiques' des justifications de transfert.
* Default 'stay observer' value => surchargeable par chaque utilisateur
* Default 'transfer mode' for group on requalification => surchargeable par chaque utilisateur
