<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tasks')]
#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    #[Route('/', name: 'task_list', methods: ['GET'])]
    public function listAction(TaskRepository $taskRepository): Response
    {
        return $this->render('task/list.html.twig', [
            'tasks' => $taskRepository->findAll(),
        ]);
    }

    #[Route('/create', name: 'task_create', methods: ['GET', 'POST'])]
    public function createAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($task);
            $entityManager->flush();
            $this->addFlash('success', "La tâche a bien été ajoutée.");

            return $this->redirectToRoute('task_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/create.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('task_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/toggle', name: 'task_toggle', methods: ['POST'])]
    public function toggleTaskAction(Task $task, EntityManagerInterface $entityManager): Response
    {
        $task->toggle(!$task->isDone());
        $entityManager->flush();

        $message = $task->isDone()
        ? sprintf('La tâche %s a bien été marquée comme faite.', $task->getTitle())
        : sprintf('La tâche %s a bien été marquée comme non faite.', $task->getTitle());

        $this->addFlash('success', $message);

        return $this->redirectToRoute('task_list');
    }

    #[Route('/{id}', name: 'task_delete', methods: ['POST'])]
    public function deleteTaskAction(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$task->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($task);
            $entityManager->flush();

            $this->addFlash('success', 'La tâche a bien été supprimée.');
        }

        return $this->redirectToRoute('task_list', [], Response::HTTP_SEE_OTHER);
    }
}
