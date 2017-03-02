<?php

namespace Oro\Bundle\ConfigBundle\Migration;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class RenameConfigArrayKeyQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $configValueName;

    /**
     * @var string
     */
    protected $configValueSection;

    /**
     * @var string
     */
    protected $oldKeyName;

    /**
     * @var string
     */
    protected $newKeyName;

    public function __construct($configValueName, $configValueSection, $oldKeyName, $newKeyName)
    {
        $this->configValueName = $configValueName;
        $this->configValueSection = $configValueSection;
        $this->oldKeyName = $oldKeyName;
        $this->newKeyName = $newKeyName;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $selectQuery = 'SELECT id, array_value FROM oro_config_value WHERE name = :name AND section = :section';
        $selectQueryTypes = ['name' => 'string', 'section' => 'string'];
        $selectQueryParameters = ['name' => $this->configValueName, 'section' => $this->configValueSection];

        $this->logQuery($logger, $selectQuery, $selectQueryParameters, $selectQueryTypes);
        if ($dryRun) {
            return;
        }

        $updateQuery = 'UPDATE oro_config_value SET array_value = :array_value WHERE id = :id';
        $updateQueryTypes = ['array_value' => 'array', 'id' => 'integer'];

        $selectStatement = $this->connection->prepare($selectQuery);
        $selectStatement->execute($selectQueryParameters);
        while ($row = $selectStatement->fetch()) {
            $originalValue = $this->deserialize($row['array_value']);
            $convertedValue = $this->convert($originalValue);
            if ($originalValue !== $convertedValue) {
                $updateParameters = ['array_value' => $convertedValue, 'id' => $row['id']];
                $this->logQuery($logger, $updateQuery, $updateParameters, $updateQueryTypes);
                $this->connection->executeUpdate($updateQuery, $updateParameters, $updateQueryTypes);
            }
        }
    }

    /**
     * @param array $values
     * @return array
     */
    protected function convert(array $values)
    {
        if (array_key_exists($this->oldKeyName, $values)) {
            $values[$this->newKeyName] = $values[$this->oldKeyName];
            unset($values[$this->oldKeyName]);
        }

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = $this->convert($value);
            }
        }
        return $values;
    }

    /**
     * @param string $serializedValue
     * @return array
     */
    protected function deserialize($serializedValue)
    {
        $arrayType = Type::getType(Type::TARRAY);
        $platform = $this->connection->getDatabasePlatform();
        return $arrayType->convertToPHPValue($serializedValue, $platform);
    }
}
