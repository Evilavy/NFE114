<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:delete-all-java-messages',
    description: 'Supprime tous les messages de l\'API Java',
)]
class DeleteAllJavaMessagesCommand extends Command
{
    private string $javaApiUrl = 'http://localhost:8080/demo-api/api';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Suppression de tous les messages de l\'API Java');

        try {
            // Récupérer tous les messages
            $response = $this->httpClient->request('GET', $this->javaApiUrl . '/messages');
            
            if ($response->getStatusCode() !== 200) {
                $io->error('Impossible de récupérer les messages de l\'API Java.');
                return Command::FAILURE;
            }

            $messages = $response->toArray();
            $count = count($messages);

            if ($count === 0) {
                $io->info('Aucun message à supprimer dans l\'API Java.');
                return Command::SUCCESS;
            }

            $io->note("Nombre de messages à supprimer : $count");

            if (!$io->confirm('Êtes-vous sûr de vouloir supprimer tous les messages de l\'API Java ?', false)) {
                $io->warning('Opération annulée.');
                return Command::SUCCESS;
            }

            $deletedCount = 0;
            $errorCount = 0;

            foreach ($messages as $message) {
                try {
                    $deleteResponse = $this->httpClient->request('DELETE', $this->javaApiUrl . '/messages/' . $message['id']);
                    
                    if ($deleteResponse->getStatusCode() === 200) {
                        $deletedCount++;
                    } else {
                        $errorCount++;
                        $io->warning("Erreur lors de la suppression du message ID: " . $message['id']);
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $io->warning("Erreur lors de la suppression du message ID: " . $message['id'] . " : " . $e->getMessage());
                }
            }

            if ($errorCount === 0) {
                $io->success("$deletedCount messages supprimés avec succès de l'API Java.");
            } else {
                $io->warning("$deletedCount messages supprimés, $errorCount erreurs.");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la communication avec l\'API Java : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
