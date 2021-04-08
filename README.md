# Symfony 5

Il faut :
- un client CLI Symfony 4.x 
- composer 2.x
- php 7.x ou 8.x

### On peut installer composer de deux manière : locally ou global.

    Pour local, aller dans le repo, télécharger puis run l'installer : 
    ```
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
    ```
    On se retrouve avec un compser.phar (du binaire PHP archivé) et qui sert à être utilisé en ligne de commandes. 
    Pour le run : php composer.phar

    Ensuite dans composer.json -> Equivalent package.json

### Pareil pour symfony

    Install : 
    wget https://get.symfony.com/cli/installer -O - | bash

    L'exporter en global :
    mv /home/lionel/.symfony/bin/symfony /usr/local/bin/symfony


### On peut avoir deux types de projets : micro-framework ou monolithiques (avec tous les composants shippés).

On va utiliser micro-framework (par défaut basé sur symfony/skeleton). Si jamais besoin de monolithique alors --full. 
Par exemple en monolithique, en créant un controller on a même un twig shippé avec etc...

Donc dans notre repo : 
    symfony new painterest

Pour run le server plusieurs choix (-d pour detach):
    php -S 127.0.0.1:3000 -t public/
    symfony server:start
    symfony serve

Pour un petit certificat (mais bon) : 
    symfony server:ca:install
    symfony server:start --no-tls (pour run sans tls)

Pour un petit domaine en dev: 
    symfony proxy:domain:attach painterest-clone

## Structure du projet

- bin/ qui contient des executables/binaires pour être executé sur la console (par exemple php bin/console server:log etc..)
    Donc bin/console pour avoir la console.
   

- config/ écrit en yaml pour les routes et services, et autres configs
    bundles.php -> Bundle comme un plugin, qui contient aussi nos paquets.

- public/ qui sera la racine ou pointe Apache avec tous les assets : images/javascript.
Les utilisateurs auront accès à ce dossier.

- src/ qui contient le code

- var/ pour le cache, les logs

- composer.json pointe vers src/ dans autoload (équivalent package.json)
    Parmi les paquets:
    - On a flex qui permet de gérer les alias des paquets, par exemple maker au lieu de maker-bundle etc
- tests pour les unitaires et fonctionnels.
- vendor propre à composer qui contient tous les paquets (node_modules)
- .env pour gérer la connexion à la BDD (initialisé par l'environnement DEV et une APP_SECRET et DATABASE_URL)

## ENV
Dans services.yaml on peut avoir les paramètres du container : des variables pour nos configs. Commence par parameter en yaml.
```
parameter:
    toto: "coucou"
```

Pour récupérer des variables : %env(DATABASE_URL)% et pour récupérer des variables comme paramètre du container : %TOTO%


## Packets 

- Maker va nous faciliter la création des controllers, etc...

- composer require doctrine/annotations (pour faire tourner Maker)

- Sensio : injection d'entité via ParamConverter

## Implémentation

symfony console make:controller

### Controller (le CEO, chef d'orchestre)

C'est le chef d'orchestre, qui n'est qu'une classe PHP.
On a des metadonnées sous forme d'annotations /****/ normalement (ici # mais bon).
Toutes nos classes controller héritent de AbsractController (avec la méthode json par exemple)
Dans la route /pins, on renvoie la fonction index()
Il vaut mieux typé ses retours comme : Response pour comprendre les erreurs.

On doit retourner un objet de type Response si on veut retourner autre chose que ce JSON (par exemple).
    return new Response('Hello world');
ou retourner une view
    return $this->render('pins/index.html.twig');

```
class PinsController extends AbstractController
{
    #[Route('/pins', name: 'pins')]
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/PinsController.php',
        ]);
    }
}
```

### Routing

Pusieurs moyens: annots, ou encore
Dans config/routes.yaml: on spécifie un nom, un path ainsi que son controlleur associé. Exemple
Et si on précise pas l'action ::index, il faut mettre dans le controller, au lieu de la fonction index, __invoke() (mais bon)
```
index:
  path: /
  controller: App\Controller\PinsController::index
```

symfony console debug:router

On utilise les noms des routes comme variable pour pouvoir les ré-utiliser, notamment pour les vues.
Le nom est crée par le controller -> RedAppleController::Create devient red_apple_create (on utilise l'action)
par défaut. Mais on peut crée le nom de la route dans annotation.

### Accès BDD - ORM (Doctrine)

Doctrine est notre ORM. Avec l'ORM on n'écrit pas directement notre SQL, mais on passe 
par des classes qui sont des entités (au lieu de faire INSERT INTO, ce sera une instanciation de classe à travers des méthodes etc...)
On reste donc dans le monde PHP.
Cela permet de ne pas gérer le changement de SGBD (mais bon, SQL reste très uniforme donc bon)

Créer une table:
    symfony console make:entity

Migrations:
    symfony console make:migration
    symfony console doctrine:migrations:migrate

La migration permet de valider et de traquer les changements SQL.
Pour revenir à la précédente:
    doctrine:migrations:migrate prev

Il faut donc aller dans .env DATABASE_URL pour rentrer les configs dans DATABASE_URL.
Avec symfony console doctrine:database:create on crée la bdd : le nom va s'appuyer sur la variable d'env justement.

> mysql -u root -p 

On va mettre dans le controller ceci : 
EntityManager va hériter de AbstractController pour gérer les entités: persistances, etc...
Et flush() pour lancer les persist()
```
    $pin = new Pin;

    $pin->setTitle('Title1');
    $pin->setDescription('Description 1');

    $em = $this->getDoctrine()->getManager();

    $em->persist($pin);
    $em->flush();
    
    return $this->render('pins/index.html.twig');
```

Plus propre pour le em mettre directement dans les paramètres de la fonction index comme ceci 
>  public function index(EntityManagerInterface $em): Response.

CRUD :
Entity représente un PIN (avec title, description etc), tandis que Repository représente la TABLE de TOUS LES PINS (donc avec les méthodes CRUD associés).

- R - On va s'appuyer sur Entity pour récupérer des méthodes comme Find(), findAll() etc...
Donc dans notre controller on va se lier à une entity (Pin) puis  dumper toute la table
```
    $repo = $em->getRepository(Pin::class);
    dd = $repo->findAll();
    $pins = $repo->findAll();
    return $this->render('pins/index.html.twig', ['pins' => $pins]);
```

Après, au lieu d'utiliser EM on peut directement accèder au Pin Repository
```
    use App\Repository\PinRepository;
    public function index(PinRepository $repo): Response
    {
        return $this->render('pins/index.html.twig', ['pins' => $repo->findAll()]);
    }
```

- C - On va récupérer les champs fournis par un formulaire à travers une méthode POST. Donc dans controller.
(Si méthode GET alors $request->query->all();). Ensuite on fait une redirection
```
   if($request->isMethod('POST'))
    {   
        $data = $request->request->all();

        $pin = new Pin;

        $pin->setTitle($data['title']);
        $pin->setDescription($data['description']);
        
        $em->persist($pin);
        $em->flush();

        return $this->redirect('/');
    };
```

Autrement, on peut accèder directement à la bdd depuis la console:
> symfony console doctrine:query:sql "DELETE from Pins"

### FormBuilder 

```
public function create(Request $request, EntityManagerInterface $em)
    {
        $form = $this->createFormBuilder()
            ->add('title', TextType::class)
            ->add('description',TextareaType::class)
            ->add('submit', SubmitType::class, ['label' => 'Create Pin'])
            ->getForm()
        ;

        // Il n'y a quelque chose dans request que en POST  
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) 
        {   
            $data = $form->getData();

            $pin = new Pin;

            $pin->setTitle($data['title']);
            $pin->setDescription($data['description']);

            $em->persist($pin);
            $em->flush();

            return $this->redirectToRoute('pins');
        };

        return $this->render('pins/create.html.twig', ['monFormulaire' => $form->createView()]);
    }
```

Du côté twig, il faut faire form(monFormulaire).

Ou alors,on peut passer l'objet directement dans le formulaire qui va surcharger l'objet.
```
    $pin = new Pin;
    $form = $this->createFormBuilder($pin)
    $em->persist($pin); // ou $form->getData()
    $em->flush();
```
### Debug etc..

Symfo propose dump() plutôt que var_dump() pour un meilleur affichage.
Donc faire dd() (dump et die pour passer à la suite du script).

composer require debug-pack
Les dump passent donc dans le profiler, on ne les voit plus à l'écran comme avec des print() par exemple.

### Un peu de twig ? 

```
<h6> <a href="{{ path('app_pins_show', {id: pin.id})}}">{{ pin.title }} </a></h6>
```