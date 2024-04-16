<?php

namespace App\Controller;

use App\Service\NoteService;
use App\Service\TokenService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    public function __construct(
        private readonly NoteService $noteService,
        private readonly TokenService $tokenService
    )
    {
    }

    /**
     * @throws Exception
     */
    #[Route('/', name: 'request_post', methods: ['POST'])]
    public function index(Request $request): Response
    {
        $str = urldecode($request->getContent());
        parse_str($str, $res);

        foreach ($res as $key => $item) {

            switch ($key) {
                case 'contacts':
                    $operation = array_key_first($item);
                    switch ($operation){
                        case 'update':
                            $this->handleUpdateContact($item[$operation]);
                            break;
                        case 'add':
                            $this->handleCreateRecord($item[$operation], 'contacts');
                            break;
                    }
                    break;
                case 'leads':
                    $operation = array_key_first($item);
                    switch ($operation){
                        case 'update':
                            $this->handleUpdateLead($item[$operation]);
                            break;
                        case 'add':
                            $this->handleCreateRecord($item[$operation], 'leads');
                            break;
                    }
                    break;
            }
        }

        $response = new Response('');
        return $this->json($response);
    }

    /**
     * @throws Exception
     */
    private function handleUpdateContact(array $data): void
    {
        $token = $this->tokenService->getToken();

        foreach ($data as $item) {
            $fieldValues = [];
            $customFields = $item['custom_fields'] ?? [];

            foreach ($customFields as $customField) {

                $name = $customField['name'];
                $value = $customField['values'][0]['value'];

                $fieldValues[] = ' ' . $name . ' = ' . $value;
            }

            $newNote = implode(';', $fieldValues);
            $newNote .= ' Updated at: ' . date('y-m-d h:i:s', $item['last_modified']);

            $this->noteService->setNote($token['access_token'], 'contacts', $item['id'], $newNote);
        }
    }

    /**
     * @throws Exception
     */
    private function handleUpdateLead(array $data): void
    {
        $token = $this->tokenService->getToken();

        foreach ($data as $item) {
            $fieldValues = [];
            if ($item['name']) $fieldValues[] = ' Name: ' . $item['name'];
            if ($item['price']) $fieldValues[] = ' Price: ' . $item['price'];
                else $fieldValues[] = ' Price: 0';
            if ($item['status_id']) $fieldValues[] = ' Status: ' . $item['status_id'];

            $newNote = implode(';', $fieldValues);
            $newNote .= ' Updated at: ' . date('y-m-d h:i:s', $item['last_modified']);

            $this->noteService->setNote($token['access_token'], 'leads', $item['id'], $newNote);
        }
    }

    /**
     * @throws Exception
     */
    private function handleCreateRecord(array $data, string $entityType): void
    {
        $token = $this->tokenService->getToken();

        foreach ($data as $item) {
            $newNote = 'Name: ' . $item['name'] . '; user_id: ' . $item['responsible_user_id'];
            $newNote .= ' Created at: ' . date('y-m-d h:i:s', $item['created_at']);

            $this->noteService->setNote($token['access_token'], $entityType, $item['id'], $newNote);
        }
    }
}
