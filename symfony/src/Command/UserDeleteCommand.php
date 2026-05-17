<?php

declare(strict_types=1);

namespace App\Command;

use App\Interface\UserInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:user:delete'
)]
class UserDeleteCommand extends Command
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UserInterface $userInterface,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user to delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = new QuestionHelper();

        $email = $input->getArgument('email');

        $user = $this->userInterface->findByEmail($email);
        if (null === $user) {
            $io->error($this->translator->trans('command.user.delete.not_found', ['%email%' => $email]));

            return Command::FAILURE;
        }

        $confirmQuestion = new ConfirmationQuestion(
            $this->translator->trans(
                'command.user.delete.confirm',
                [
                    '%username%' => $user->getUsername(),
                    '%email%' => $user->getEmail(),
                ]
            ) . ' (yes/no) [no] ',
            false
        );

        if (!$helper->ask($input, $output, $confirmQuestion)) {
            $io->warning($this->translator->trans('command.user.delete.cancelled'));

            return Command::SUCCESS;
        }

        $username = $user->getUsername();
        $this->userInterface->deleteAdmin($user);

        $io->success($this->translator->trans('command.user.delete.success', ['%username%' => $username]));

        return Command::SUCCESS;
    }
}
