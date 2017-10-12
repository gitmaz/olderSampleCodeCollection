<?php

namespace InventoryApi\ServiceManagement\Repository;

use InventoryApi\Repository\AbstractEntityRepository;
use InventoryApi\ServiceManagement\Entity\LogiServiceType;

class LogiServiceTypeRepository extends AbstractEntityRepository
{

    protected $entityClassName = \EntityNames::LOGI_SERVICE_TYPE;


    /*
     * @param \EntityManager $em
     */
    public function __construct($em)
    {
        $this->_em = $em;

        $this->class = $this->entityClassName;

        $this->_entityName = $this->entityClassName;
    }

    /*
     * @returns array of LogiServiceType
     */
    public function getAll()
    {
        return $this->findAll();
    }

    /*
     * todo: use LogicalEntity instead out of this class.This is a patch .right usage should not use pure sql for invApi consistency.
     */
    public function getEntityNameFromLogicalEntity($id)
    {
        $sql = " select entity_name from logical_entity where logical_entity_id=$id
                        ";

        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();

        $entityName = $stmt->fetchAll();

        return $entityName[0]["ENTITY_NAME"];
    }

    /**  sample filtering
     * Find By Name of ServiceType
     *
     * @param string $serviceTypeName
     *
     * @return $serviceTypes[]
     */
    public function findServiceTypeByName($serviceTypeName)
    {
        $serviceTypes = $this->findBy([
            'serviceTypeName' => $serviceTypeName,
        ], [
            'serviceTypeName' => 'DESC',
        ]);

        return $serviceTypes;
    }

    /*
     *  This will create a servicetype  record in LOGI_SERVICE_TYPE table
     *  Note that $serviceTypeId should point to an existing Logical Entity Id dedicated to that service
     *
     * @param integer $serviceTypeId
     * @param string $serviceTypeName
     * @param string $serviceTypeDiscoveryKeys
     * @param string $serviceTypeHandOverKeys
     */
    public function addServiceType($serviceTypeId, $serviceTypeName, $serviceTypeDiscoveryKeys, $serviceTypeHandOverKeys)
    {
        $logiServiceType = new LogiServiceType();
        $logiServiceType->setServiceTypeId($serviceTypeId);
        $logiServiceType->setServiceTypeName($serviceTypeName);
        $logiServiceType->setServiceTypeDiscoveryKeys($serviceTypeDiscoveryKeys);
        $logiServiceType->setServiceTypeHandOverKeys($serviceTypeHandOverKeys);

        $this->add($logiServiceType);
    }


    /*
    *  This will update a servicetype  record in LOGI_SERVICE_TYPE table
    *  Note that $serviceTypeId should point to an existing Logical Entity Id dedicated to that service
    *
    * @param integer $serviceTypeId
    * @param string $serviceTypeName
    * @param string $serviceTypeDiscoveryKeys
    * @param string $serviceTypeHandOverKeys
    */
    public function updateServiceType($serviceTypeId, $serviceTypeName, $serviceTypeDiscoveryKeys, $serviceTypeHandOverKeys)
    {
        //$logiServiceType = $this->findBy(['serviceTypeId'       => $serviceTypeId]);
        $logiServiceType = $this->find($serviceTypeId);
        if (!$logiServiceType) {
            $logiServiceType = new LogiServiceType();
            $logiServiceType->setServiceTypeId($serviceTypeId);
        }
        $logiServiceType->setServiceTypeName($serviceTypeName);
        $logiServiceType->setServiceTypeDiscoveryKeys($serviceTypeDiscoveryKeys);
        $logiServiceType->setServiceTypeHandOverKeys($serviceTypeHandOverKeys);

        $serviceTypeDiscoveryKeysParts = explode("=", $serviceTypeDiscoveryKeys);
        if (count($serviceTypeDiscoveryKeysParts) >= 2) {
            $mainKeyName = $serviceTypeDiscoveryKeysParts[0];
            $logiServiceType->setServiceTypeMainKeyName($mainKeyName);
        }


        $this->_em->persist($logiServiceType);
        $this->_em->flush();
    }

    /* //todo: move to corresponding repos and call them from manager (I am currently keeping Tomas repos intact)
     * returns details of service type attached to specific logical configuration of type service
     *
     * @return LogiServiceType
     */
    public function getServiceTypeFromLogicalConfiguration($serviceLogicalconfigurationId, $useHistoryTable = false)
    {

        $logEntityId = $this->getLogicalEntityIdFromLogicalConfiguration($serviceLogicalconfigurationId, $useHistoryTable);
        if ($logEntityId == null) {
            return null;
        }

        $logiServiceTypeRepo = new LogiServiceTypeRepository($this->_em);

        $logiServiceType = $logiServiceTypeRepo->find($logEntityId);

        return $logiServiceType;

    }

    /* //todo: use service.xml to access LogicalConfigurationRepository
     *
     */
    private function getLogicalEntityIdFromLogicalConfiguration($serviceLogicalconfigurationId, $useHistoryTable = false)
    {

        if (!$useHistoryTable) {
            $sql = " select logical_entity_id from logical_configuration where log_conf_id=$serviceLogicalconfigurationId ";
        } else {
            $sql = " select logical_entity_id from logical_configuration_history where log_conf_id=$serviceLogicalconfigurationId ";

        }
        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();

        $logEntityId = $stmt->fetchAll();

        return $logEntityId[0]["LOGICAL_ENTITY_ID"];

    }

    /* //todo : move to ParameterTypeRepository where this realy belongs
     *
     */
    public function getAllServiceKeyParameters()
    {
        $sql = " select parameter_type_name from parameter_type where is_service_key=1 ";

        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();

        $results = $stmt->fetchAll();

        $parameterNames = [];
        foreach ((array)$results as $result) {
            $parameterNames[] = $result["PARAMETER_TYPE_NAME"];
        }
        return $parameterNames;
    }

    public function AddParameterToAvailableParameters($parameterName, $parameterType)
    {
        $sql = " insert into parameter_type (parameter_type_name,final_type,is_service_key) values('$parameterName','$parameterType',1) ";

        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();

        $this->_em->flush();

    }


}