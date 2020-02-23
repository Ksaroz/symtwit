<?php


namespace App\Controller;


use App\Entity\MicroPost;
use App\Entity\User;
use App\Form\MicroPostType;
use App\Repository\MicroPostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


/**
 * @Route("/micro-post")
 * we can control each of the action of this controller using below security voter in here.
 */
class MicroPostController extends AbstractController
{

    /**
     * @var MicroPostRepository
     */
    private $microPostRepository;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var FlashBagInterface
     */
    private $flashBag;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(
        MicroPostRepository $microPostRepository,
        FormFactoryInterface $formFactory,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        FlashBagInterface $flashBag,
        AuthorizationCheckerInterface $authorizationChecker
    ) {

        $this->microPostRepository = $microPostRepository;
        $this->formFactory = $formFactory;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->flashBag = $flashBag;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @Route("/", name="micro_post_index")
     */
    public function index()
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($currentUser instanceof User)
        {
            $posts = $this->microPostRepository->findAllByUsers($currentUser->getFollowing());
        }
        else
        {
            $posts = $this->microPostRepository->findBy([], ['time' => 'DESC']);
        }
        return $this->render('micro-post/index.html.twig', [
            'posts' => $posts
        ]);
    }

    /**
     * @Route("/edit/{id}", name="micro_post_edit")
     *
     * #the below security annotation is more convienient way of using voter grant method in annotation for user access
     * @Security("is_granted('edit', microPost)", message="Access Denied")
     */
    public function edit(MicroPost $microPost, Request $request)
    {
        //we can use this if we have extends any base controller like controller and AbstractController
        //$this->denyAccessUnlessGranted('edit', $microPost);

// or we can use below method if we dont extends any base controller like Controller and AbstractController
// by using authirizationCheckerInterface in above contructor..
//        if (!$this->authorizationChecker->isGranted('edit', $microPost))
//        {
//            throw new UnauthorizedHttpException();
//        }


        $form = $this->formFactory->create(MicroPostType::class, $microPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($microPost);
            $this->entityManager->flush();

            return new RedirectResponse($this->router->generate('micro_post_index'));
        }

        return $this->render('micro-post/add.html.twig',
            [
                'form' => $form->createView()
            ]);
    }

    /**
     * @Route("/delete/{id}", name="micro_post_delete")
     * @Security("is_granted('delete', microPost)", message="Access Denied")
     */
    public function delete(MicroPost $microPost)
    {
        $this->entityManager->remove($microPost);
        $this->entityManager->flush();

        $this->flashBag->add('success', 'Micro post has deleted');

        return new RedirectResponse($this->router->generate('micro_post_index'));
    }

    /**
     * @Route("/add", name="micro_post_add")
     * @Security("is_granted('ROLE_USER')")
     */
    public function add(Request $request)
    {
        $user = $this->getUser();
        $microPost = new MicroPost();
//        $microPost->setTime(new \DateTime());
        $microPost->setUser($user);

        $form = $this->formFactory->create(MicroPostType::class, $microPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($microPost);
            $this->entityManager->flush();

            return new RedirectResponse($this->router->generate('micro_post_index'));
        }

        return $this->render('micro-post/add.html.twig',
            [
                'form' => $form->createView()
            ]);
    }

    /**
     * @param User $userWithPosts
     * @Route("/user/{username}", name="micro_post_user")
     */
    public function userPost(User $userWithPosts)
    {
        return $this->render('micro-post/user-post.html.twig', [
            'posts' => $this->microPostRepository->findBy(
                ['user' => $userWithPosts],
                ['time' => 'DESC']),
            'user' => $userWithPosts,
        ]);
    }

    /**
     * @Route("/{id}", name="micro_post_post")
     */
    public function post(MicroPost $post)
    {
//        $post = $this->microPostRepository->find($id);

        return $this->render('micro-post/post.html.twig',
            [
                'post' => $post
            ]);
    }
}
