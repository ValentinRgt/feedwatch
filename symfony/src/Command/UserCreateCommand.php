<?php

declare(strict_types=1);

namespace App\Command;

use App\Interface\UserInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:user:create'
)]
class UserCreateCommand extends Command
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UserInterface $userInterface,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = new QuestionHelper();

        $emailQuestion = new Question($this->translator->trans('command.user.create.email'));
        $emailQuestion->setValidator(function ($value) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new Exception($this->translator->trans('command.user.create.email_invalid'));
            }

            $existingUser = $this->userInterface->alreadyExist($value);
            if ($existingUser) {
                throw new Exception(
                    $this->translator->trans(
                        'command.user.create.email_exists',
                        [
                            '%email%' => $value,
                        ]
                    )
                );
            }

            return $value;
        });
        $emailQuestion->setMaxAttempts(3);
        $email = $helper->ask($input, $output, $emailQuestion);

        $usernameQuestion = new Question($this->translator->trans('command.user.create.username'));
        $usernameQuestion->setValidator(function ($value) {
            if (empty($value) || strlen($value) < 3) {
                throw new Exception($this->translator->trans('command.user.create.username_invalid'));
            }

            return $value;
        });
        $usernameQuestion->setMaxAttempts(3);
        $username = $helper->ask($input, $output, $usernameQuestion);

        $passwordQuestion = new Question($this->translator->trans('command.user.create.password'));
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);
        $passwordQuestion->setValidator(function ($value) {
            if (empty($value) || strlen($value) < 8) {
                throw new Exception($this->translator->trans('command.user.create.password_invalid'));
            }

            return $value;
        });
        $passwordQuestion->setMaxAttempts(3);
        $password = $helper->ask($input, $output, $passwordQuestion);

        $confirmPasswordQuestion = new Question($this->translator->trans('command.user.create.confirm_password'));
        $confirmPasswordQuestion->setHidden(true);
        $confirmPasswordQuestion->setHiddenFallback(false);
        $confirmPassword = $helper->ask($input, $output, $confirmPasswordQuestion);

        if ($password !== $confirmPassword) {
            $io->error($this->translator->trans('command.user.create.password_mismatch'));

            return Command::FAILURE;
        }

        $this->userInterface->createAdmin($email, $username, $password);

        $io->success($this->translator->trans('command.user.create.success', ['%username%' => $username]));

        return Command::SUCCESS;
    }
}
