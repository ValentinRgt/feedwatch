<?php

declare(strict_types=1);

namespace App\Command;

use App\Interface\UserInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:user:update'
)]
class UserUpdateCommand extends Command
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
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user to update');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = new QuestionHelper();

        $email = $input->getArgument('email');

        $user = $this->userInterface->findByEmail($email);
        if (null === $user) {
            $io->error($this->translator->trans('command.user.update.not_found', ['%email%' => $email]));

            return Command::FAILURE;
        }

        $usernameQuestion = new Question(
            $this->translator->trans('command.user.update.username', ['%username%' => $user->getUsername()]),
            $user->getUsername()
        );
        $usernameQuestion->setValidator(function ($value) {
            if (empty($value) || strlen($value) < 3) {
                throw new Exception($this->translator->trans('command.user.update.username_invalid'));
            }

            return $value;
        });
        $usernameQuestion->setMaxAttempts(3);
        $username = $helper->ask($input, $output, $usernameQuestion);

        $passwordQuestion = new Question($this->translator->trans('command.user.update.password'));
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);
        $passwordQuestion->setValidator(function ($value) {
            if (!empty($value) && strlen($value) < 8) {
                throw new Exception($this->translator->trans('command.user.update.password_invalid'));
            }

            return $value;
        });
        $passwordQuestion->setMaxAttempts(3);
        $password = $helper->ask($input, $output, $passwordQuestion);

        if (!empty($password)) {
            $confirmPasswordQuestion = new Question(
                $this->translator->trans('command.user.update.confirm_password')
            );
            $confirmPasswordQuestion->setHidden(true);
            $confirmPasswordQuestion->setHiddenFallback(false);
            $confirmPassword = $helper->ask($input, $output, $confirmPasswordQuestion);

            if ($password !== $confirmPassword) {
                $io->error($this->translator->trans('command.user.update.password_mismatch'));

                return Command::FAILURE;
            }
        }

        $this->userInterface->updateAdmin($user, $username, empty($password) ? null : $password);

        $io->success($this->translator->trans('command.user.update.success', ['%username%' => $username]));

        return Command::SUCCESS;
    }
}
