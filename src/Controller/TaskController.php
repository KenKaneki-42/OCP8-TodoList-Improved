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

#[Route('/tasks')]
#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    #[Route('/', name: 'task_list', methods: ['GET'])]
    public function list(TaskRepository $taskRepository): Response
    {
        return $this->render('task/list.html.twig', [
            'tasks' => $taskRepository->findAll(),
        ]);
    }

    #[Route('/create', name: 'task_create', methods: ['GET', 'POST'])]
    public function create(Request $request, TaskRepository $taskRepository, Security $security): Response
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

            return $this->redirectToRoute('task_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/create.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task, TaskRepository $taskRepository): Response
    {
        // 2 ways to check if the user has permission to edit the task.

        // Check if the user has permission to edit the task (using the isGranted method)
        // if (!$this->security->isGranted('EDIT', $task)) {
        //     throw new AccessDeniedException('You do not have permission to edit this task.');
        // }

        // Check if the user has permission to edit the task (using the denyAccessUnlessGranted method from the AbstractController and the Voter)
        $this->denyAccessUnlessGranted('edit', $task);

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $taskRepository->save($task, true);

            return $this->redirectToRoute('task_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/toggle', name: 'task_toggle', methods: ['POST'])]
    public function toggle(Task $task, TaskRepository $taskRepository): Response
    {
        // Check if the user has permission to toggle the task (Voter)
        $this->denyAccessUnlessGranted('toggle', $task);

        $task->toggle(!$task->isDone());
        $taskRepository->save($task, true);

        $message = $task->isDone()
        ? sprintf('La tâche %s a bien été marquée comme faite.', $task->getTitle())
        : sprintf('La tâche %s a bien été marquée comme non faite.', $task->getTitle());

        $this->addFlash('success', $message);

        return $this->redirectToRoute('task_list');
    }

    #[Route('/{id}', name: 'task_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task, TaskRepository $taskRepository): Response
    {
        // Check if the user has permission to delete the task (Voter)
        $this->denyAccessUnlessGranted('delete', $task);

        if ($this->isCsrfTokenValid('delete'.$task->getId(), $request->getPayload()->getString('_token'))) {
            $taskRepository->remove($task, true);

            $this->addFlash('success', 'La tâche a bien été supprimée.');
        }

        return $this->redirectToRoute('task_list', [], Response::HTTP_SEE_OTHER);
    }
}
