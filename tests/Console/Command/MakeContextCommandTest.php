<?php

declare(strict_types=1);

namespace DddForge\Tests\Console\Command;

use DddForge\Console\Command\Factory\MakeContextCommandFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class MakeContextCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private Filesystem $filesystem;
    private string $testDir;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->testDir = sys_get_temp_dir() . '/ddd-forge-test-' . uniqid();

        $application = new Application();
        $application->add(MakeContextCommandFactory::create());
        $command = $application->find('make:context');
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->testDir)) {
            $this->filesystem->remove($this->testDir);
        }
    }

    public function test_givenValidContextName_whenExecutingCommand_thenCreatesBasicStructure(): void
    {
        // Given
        $contextName = 'UserManagement';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertDirectoryExists($this->testDir . '/Usermanagement');
        $this->assertDirectoryExists($this->testDir . '/Usermanagement/Domain');
        $this->assertDirectoryExists($this->testDir . '/Usermanagement/Application');
        $this->assertDirectoryExists($this->testDir . '/Usermanagement/Infrastructure');
        $this->assertDirectoryExists($this->testDir . '/Usermanagement/UI');
        $this->assertStringContainsString('context ready', $this->commandTester->getDisplay());
    }

    public function test_givenStandardTemplate_whenExecutingCommand_thenCreatesSublayers(): void
    {
        // Given
        $contextName = 'Billing';
        $template = 'standard';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
            '--template' => $template,
            '--with-sublayers' => true,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertDirectoryExists($this->testDir . '/Billing/Domain/Model');
        $this->assertDirectoryExists($this->testDir . '/Billing/Domain/Service');
        $this->assertDirectoryExists($this->testDir . '/Billing/Domain/Repository');
        $this->assertDirectoryExists($this->testDir . '/Billing/Application/Command');
        $this->assertDirectoryExists($this->testDir . '/Billing/Application/Query');
        $this->assertDirectoryExists($this->testDir . '/Billing/Application/Handler');
    }

    public function test_givenCqrsTemplate_whenExecutingCommand_thenCreatesCqrsStructure(): void
    {
        // Given
        $contextName = 'Orders';
        $template = 'cqrs';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
            '--template' => $template,
            '--with-sublayers' => true,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertDirectoryExists($this->testDir . '/Orders/Domain/Read');
        $this->assertDirectoryExists($this->testDir . '/Orders/Domain/Write');
        $this->assertDirectoryExists($this->testDir . '/Orders/Application/Bus');
        $this->assertDirectoryExists($this->testDir . '/Orders/Infrastructure/Read');
        $this->assertDirectoryExists($this->testDir . '/Orders/Infrastructure/Write');
    }

    public function test_givenEventSourcingTemplate_whenExecutingCommand_thenCreatesEventSourcingStructure(): void
    {
        // Given
        $contextName = 'Inventory';
        $template = 'event-sourcing';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
            '--template' => $template,
            '--with-sublayers' => true,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertDirectoryExists($this->testDir . '/Inventory/Domain/Aggregate');
        $this->assertDirectoryExists($this->testDir . '/Inventory/Domain/Event');
        $this->assertDirectoryExists($this->testDir . '/Inventory/Domain/Projection');
        $this->assertDirectoryExists($this->testDir . '/Inventory/Infrastructure/EventStore');
        $this->assertDirectoryExists($this->testDir . '/Inventory/Application/EventHandler');
    }

    public function test_givenHexagonalTemplate_whenExecutingCommand_thenCreatesHexagonalStructure(): void
    {
        // Given
        $contextName = 'Payment';
        $template = 'hexagonal';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
            '--template' => $template,
            '--with-sublayers' => true,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertDirectoryExists($this->testDir . '/Payment/Domain/Port');
        $this->assertDirectoryExists($this->testDir . '/Payment/Application/UseCase');
        $this->assertDirectoryExists($this->testDir . '/Payment/Infrastructure/Adapter');
        $this->assertDirectoryExists($this->testDir . '/Payment/UI/Adapter');
    }

    public function test_givenDryRunOption_whenExecutingCommand_thenShowsPreviewWithoutCreating(): void
    {
        // Given
        $contextName = 'Catalog';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
            '--dry-run' => true,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertDirectoryDoesNotExist($this->testDir . '/Catalog');
        $this->assertStringContainsString('Dry Run', $this->commandTester->getDisplay());
        $this->assertStringContainsString('would be created', $this->commandTester->getDisplay());
    }

    public function test_givenExistingDirectory_whenExecutingWithoutForce_thenSkipsExisting(): void
    {
        // Given
        $contextName = 'Shipping';
        $this->filesystem->mkdir($this->testDir . '/Shipping/Domain');

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Exists', $this->commandTester->getDisplay());
        $this->assertStringContainsString('already existed', $this->commandTester->getDisplay());
    }

    public function test_givenExistingDirectory_whenExecutingWithForce_thenProceedsWithCreation(): void
    {
        // Given
        $contextName = 'Notification';
        $this->filesystem->mkdir($this->testDir . '/Notification/Domain');

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
            '--force' => true,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertDirectoryExists($this->testDir . '/Notification/Domain');
        $this->assertDirectoryExists($this->testDir . '/Notification/Application');
    }

    public function test_givenGitkeepOption_whenExecutingCommand_thenCreatesGitkeepFiles(): void
    {
        // Given
        $contextName = 'Analytics';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
            '--gitkeep' => true,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertFileExists($this->testDir . '/Analytics/.gitkeep');
        $this->assertFileExists($this->testDir . '/Analytics/Domain/.gitkeep');
        $this->assertFileExists($this->testDir . '/Analytics/Application/.gitkeep');
        $this->assertStringContainsString('.gitkeep', $this->commandTester->getDisplay());
    }

    public function test_givenInvalidContextName_whenExecutingCommand_thenReturnsError(): void
    {
        // Given
        $contextName = '123Invalid';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
        ]);

        // Then
        $this->assertEquals(2, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('must start with a letter', $this->commandTester->getDisplay());
    }

    public function test_givenEmptyContextName_whenExecutingCommand_thenReturnsError(): void
    {
        // Given
        $contextName = '';

        // When
        $this->commandTester->execute(
            [
                'name' => $contextName,
                '--dir' => $this->testDir,
            ],
            ['interactive' => false]
        );

        // Then
        $this->assertEquals(2, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('cannot be empty', $this->commandTester->getDisplay());
    }

    public function test_givenInvalidTemplate_whenExecutingCommand_thenReturnsError(): void
    {
        // Given
        $contextName = 'Product';
        $invalidTemplate = 'nonexistent';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
            '--template' => $invalidTemplate,
        ]);

        // Then
        $this->assertEquals(2, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Invalid template', $this->commandTester->getDisplay());
    }

    public function test_givenKebabCaseName_whenExecutingCommand_thenConvertsToStudlyCase(): void
    {
        // Given
        $contextName = 'user-management';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertDirectoryExists($this->testDir . '/Usermanagement');
    }

    public function test_givenSnakeCaseName_whenExecutingCommand_thenConvertsToStudlyCase(): void
    {
        // Given
        $contextName = 'order_processing';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertDirectoryExists($this->testDir . '/Orderprocessing');
    }

    public function test_givenBasicTemplate_whenExecutingCommand_thenCreatesOnlyMainLayers(): void
    {
        // Given
        $contextName = 'Customer';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
            '--template' => 'basic',
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertDirectoryExists($this->testDir . '/Customer/Domain');
        $this->assertDirectoryDoesNotExist($this->testDir . '/Customer/Domain/Model');
        $this->assertDirectoryDoesNotExist($this->testDir . '/Customer/Domain/Service');
    }

    public function test_givenCustomDirectory_whenExecutingCommand_thenCreatesInCustomLocation(): void
    {
        // Given
        $contextName = 'Authentication';
        $customDir = $this->testDir . '/custom/path';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $customDir,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertDirectoryExists($customDir . '/Authentication');
        $this->assertDirectoryExists($customDir . '/Authentication/Domain');
    }

    public function test_givenMultipleTemplates_whenExecutingSequentially_thenCreatesCorrectStructures(): void
    {
        // Given
        $contextName1 = 'Context1';
        $contextName2 = 'Context2';

        // When
        $this->commandTester->execute([
            'name' => $contextName1,
            '--dir' => $this->testDir,
            '--template' => 'cqrs',
            '--with-sublayers' => true,
        ]);

        $this->commandTester->execute([
            'name' => $contextName2,
            '--dir' => $this->testDir,
            '--template' => 'hexagonal',
            '--with-sublayers' => true,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertDirectoryExists($this->testDir . '/Context1/Domain/Read');
        $this->assertDirectoryExists($this->testDir . '/Context2/Domain/Port');
        $this->assertDirectoryDoesNotExist($this->testDir . '/Context1/Domain/Port');
        $this->assertDirectoryDoesNotExist($this->testDir . '/Context2/Domain/Read');
    }

    public function test_givenValidInput_whenExecutingCommand_thenDisplaysSuccessMessage(): void
    {
        // Given
        $contextName = 'Success';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Success context ready', $output);
        $this->assertStringContainsString('directories created', $output);
        $this->assertStringContainsString('Your bounded context is ready', $output);
    }

    public function test_givenDryRunWithStandardTemplate_whenExecutingCommand_thenShowsCompleteSublayerPreview(): void
    {
        // Given
        $contextName = 'Preview';

        // When
        $this->commandTester->execute([
            'name' => $contextName,
            '--dir' => $this->testDir,
            '--template' => 'standard',
            '--with-sublayers' => true,
            '--dry-run' => true,
        ]);

        // Then
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('standard', $output);
        $this->assertStringContainsString('Domain', $output);
        $this->assertStringContainsString('Model', $output);
        $this->assertStringContainsString('Service', $output);
        $this->assertDirectoryDoesNotExist($this->testDir . '/Preview');
    }
}
