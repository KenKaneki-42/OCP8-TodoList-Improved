<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/tasks')]
#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    #[Route('/', name: 'task_list', methods: ['GET'])]
    public function list(TaskRepository $taskRepository, Security $security, TagAwareCacheInterface $cachePool): Response
    {
        $user = $security->getUser();
        if (!$user instanceof UserInterface) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        $idCache = 'getTasksList';
        $tasksList = $cachePool->get($idCache, function (ItemInterface $item) use ($taskRepository) {
            $item->tag('tasksCache');
            return $taskRepository->findUserTasks($this->getUser());
        });

        return $this->render('task/list.html.twig', [
            'tasks' => $tasksList,
        ]);
    }

    #[Route('/create', name: 'task_create', methods: ['GET', 'POST'])]
    public function create(Request $request, TaskRepository $taskRepository, Security $security, TagAwareCacheInterface $cachePool): Response
    {
        // check if the user is logged in
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $task = new Task();
        $user = $security->getUser();
        $task->setUser($user);

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $taskRepository->save($task, true);
            $this->addFlash('success', "La tâche a bien été ajoutée.");

            // Invalidate cache
            $cachePool->invalidateTags(['tasksCache']);

            return $this->redirectToRoute('task_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/create.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
            'button_label' => 'Ajouter',
        ]);
    }

    #[Route('/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task, TaskRepository $taskRepository, TagAwareCacheInterface $cachePool): Response
    {
        $this->denyAccessUnlessGranted('edit', $task);

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $taskRepository->save($task, true);
            $this->addFlash('success', "La tâche a bien été modifiée.");
            // Invalidate cache
            $cachePool->invalidateTags(['tasksCache']);

            return $this->redirectToRoute('task_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
            'button_label' => 'Modifier',
        ]);
    }

    #[Route('/{id}/toggle', name: 'task_toggle', methods: ['GET','POST'])]
    public function toggle(Task $task, TaskRepository $taskRepository, TagAwareCacheInterface $cachePool): Response
    {
        // Check if the user has permission to toggle the task (Voter)
        $this->denyAccessUnlessGranted('toggle', $task);

        $task->toggle(!$task->isDone());
        $taskRepository->save($task, true);

        // Invalidate cache
        $cachePool->invalidateTags(['tasksCache', 'tasksDoneCache']);

        $message = $task->isDone()
        ? sprintf('La tâche \'%s\' a bien été marquée comme faite.', $task->getTitle())
        : sprintf('La tâche \'%s\' a bien été marquée comme non faite.', $task->getTitle());

        $this->addFlash('success', $message);

        return $this->redirectToRoute('task_list');
    }

    #[Route('/{id}', name: 'task_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task, TaskRepository $taskRepository, TagAwareCacheInterface $cachePool): Response
    {
        // Check if the user has permission to delete the task (Voter)
        $this->denyAccessUnlessGranted('delete', $task);

        // Check if the CSRF token is valid
        $csrfToken = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$task->getId(), $csrfToken)) {

            $taskRepository->remove($task, true);
            
            $cachePool->invalidateTags(['tasksCache', 'tasksDoneCache']);

            $this->addFlash('success', 'La tâche a bien été supprimée.');
        }

        return $this->redirectToRoute('task_list', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/done', name: 'list_done', methods: ['GET'])]
    public function listDone(TaskRepository $taskRepository, TagAwareCacheInterface $cachePool): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $idCache = 'getDoneTasksList';
        $tasksList = $cachePool->get($idCache, function (ItemInterface $item) use ($taskRepository) {
            $item->tag('tasksDoneCache');
            $cachedTasksList = $taskRepository->findUserTasksDone();

            return $cachedTasksList;
        });

        return $this->render('task/list-done.html.twig', ['tasks' => $tasksList]);
    }
}
