<?php


namespace Xxii\FormBundle;


use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Pimcore\Extension\Bundle\Installer\Exception\InstallationException;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Pimcore\Model\DataObject\Fieldcollection;

class Installer extends AbstractInstaller
{
    private array $classesToInstall = [
        'XxiiForm' => 'XXII_FORM',
        'XxiiFormEntry' => 'XXII_FORM_ENTRY'
    ];
    private string $installSourcesPath;

    protected BundleInterface $bundle;

    public function __construct(
        BundleInterface $bundle
    )
    {
        $this->installSourcesPath = __DIR__ . '/./Resources/install';
        $this->bundle = $bundle;
        parent::__construct();
    }

    public function install(): void
    {
        $this->installFieldCollections();
        $this->installClasses();

        parent::install();
    }

    private function getClassesToInstall(): array
    {
        $result = [];
        foreach (array_keys($this->classesToInstall) as $className) {
            $filename = sprintf('class_%s_export.json', $className);
            $path = $this->installSourcesPath . '/class_sources/' . $filename;
            $path = realpath($path);

            if (false === $path || !is_file($path)) {
                throw new InstallationException(sprintf(
                    'Class export for class "%s" was expected in "%s" but file does not exist',
                    $className,
                    $path
                ));
            }

            $result[$className] = $path;
        }

        return $result;
    }

    private function installClasses(): void
    {
        $classes = $this->getClassesToInstall();

        $mapping = $this->classesToInstall;

        foreach ($classes as $key => $path) {
            $class = ClassDefinition::getByName($key);

            if ($class) {
                $this->output->write(sprintf(
                    '     <comment>WARNING:</comment> Skipping class "%s" as it already exists',
                    $key
                ));

                continue;
            }

            $class = new ClassDefinition();

            $classId = $mapping[$key];

            $class->setName($key);
            $class->setId($classId);

            $this->output->write(sprintf('#### <info>Class "%s" PATH.</info> ####', $path));
            $this->output->write(sprintf('#### <info>Class NAME "%s" .</info> ####', $key));
            $this->output->write(sprintf('#### <info>Class ID "%s" .</info> ####', $classId));

            $data = file_get_contents($path);
            $success = Service::importClassDefinitionFromJson($class, $data, false, true);

            if (!$success) {
                throw new InstallationException(sprintf(
                    'Failed to create class "%s"',
                    $key
                ));
            }

            //DEBUGING
            if ($success) {
                $this->output->write(sprintf(
                    '     <info>Class "%s" installed successfully.</info>',
                    $key
                ));
            } else {
                $this->output->write(sprintf(
                    '     <error>Error installing class "%s".</error>',
                    $key
                ));
            }

        }
    }

    private function installFieldCollections(): void
    {
        $fieldCollections = $this->findInstallFiles(
            $this->installSourcesPath . '/fieldcollection_sources',
            '/^fieldcollection_(.*)_export\.json$/'
        );

        foreach ($fieldCollections as $key => $path) {
            if ($fieldCollection = Fieldcollection\Definition::getByKey($key)) {
                $this->output->write(sprintf(
                    '     <comment>WARNING:</comment> Skipping field collection "%s" as it already exists',
                    $key
                ));

                continue;
            }

            $fieldCollection = new Fieldcollection\Definition();
            $fieldCollection->setKey($key);

            $data = file_get_contents($path);
            $success = Service::importFieldCollectionFromJson($fieldCollection, $data);

            if (!$success) {
                throw new InstallationException(sprintf(
                    'Failed to create field collection "%s"',
                    $key
                ));
            }

            $this->output->write(sprintf('#### <info>field collection "%s" PATH.</info> ####', $path));

            //DEBUGING
            if ($success) {
                $this->output->write(sprintf(
                    '     <info>field collection "%s" installed successfully.</info>',
                    $key
                ));
            } else {
                $this->output->write(sprintf(
                    '     <error>field collection installing class "%s".</error>',
                    $key
                ));
            }

        }
    }

    /**
     * Finds objectbrick/fieldcollection sources by path returns a result list
     * indexed by element name.
     */
    private function findInstallFiles(string $directory, string $pattern): array
    {
        $finder = new Finder();
        $finder
            ->files()
            ->in($directory)
            ->name($pattern);

        $results = [];
        foreach ($finder as $file) {
            if (preg_match($pattern, $file->getFilename(), $matches)) {
                $key = $matches[1];
                $results[$key] = $file->getRealPath();
            }
        }

        return $results;
    }

    public function needsReloadAfterInstall(): bool
    {
        return true;
    }

}