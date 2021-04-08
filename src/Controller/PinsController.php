<?php

namespace App\Controller;

use App\Entity\Pin;
use App\Repository\PinRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PinsController extends AbstractController
{
    #[Route('/', name: 'pins')]
    public function index(PinRepository $repo): Response
    {
        return $this->render('pins/index.html.twig', ['pins' => $repo->findAll()]);
    }
    /**
     * @Route("/pins/create", name="app_pins_create", methods={"GET","POST"})
     */
    public function create(Request $request, EntityManagerInterface $em)
    {
        $pin = new Pin;
        $form = $this->createFormBuilder($pin)
            ->add('title', TextType::class, [
                'required' => true,
                'attr' => ['autofocus' => true]
            ])
            ->add('description',TextareaType::class, ['attr' => ['rows' => 10, 'cols' => 50]])
            ->add('submit', SubmitType::class, ['label' => 'Create Pin'])
            ->getForm()
        ;

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) 
        {   

            $em->persist($pin);
            $em->flush();

            return $this->redirectToRoute('pins');
        };

        return $this->render('pins/create.html.twig', ['monFormulaire' => $form->createView()]);
    }
    /**
     * @Route("/pins/{id}", name="app_pins_show")
    */
    public function show(PinRepository $repo, int $id) : Response
    {
        $pin = $repo->find($id);

        if (!$pin) {
            throw $this->createNotFoundException('Pin not '. $id .' found');
        }

        return $this->render('pins/show.html.twig', compact('pin'));
    }
}
