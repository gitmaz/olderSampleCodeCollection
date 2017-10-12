<?php
/**
 * Created by PhpStorm.
 * User: Maziar Navabi
 * Date: 21/03/2016
 * Time: 10:01 AM
 */

namespace InventoryApi\ServiceManagement\Repository;

use InventoryApi\Repository\AbstractEntityRepository;
use InventoryApi\ServiceManagement\Entity\LogiService;

class LogiServiceRepository extends AbstractEntityRepository
{

    protected $entityClassName = \EntityNames::LOGI_SERVICE;

    /*
     * @VAR \EntityManager $em
     */
    public function __construct($em)
    {
        $this->_em = $em;

        $this->class = $this->entityClassName;

        $this->_entityName = $this->entityClassName;
    }
    /*
        /* //todo: make this work.makes a oracle error :can not insert null in service_type_id
         * @param integer $serviceTypeId
         * @param string $serviceName
         *
         * @return integer $newServiceId
         *
        function allocateNewService($serviceTypeId,$serviceName,$serviceDescriptor){
            $logiService=new LogiService();
            $logiService->setServiceTypeId($serviceTypeId);
            $logiService->setServiceName($serviceName);
    
            $this->add($logiService);
            $this->_em->flush();
            $newServiceId=$this->_em->getId();
    
            return $newServiceId;
        }
    */

    /*
     * @param integer $serviceTypeId
     * @param string $serviceName
     *
     * @return integer $newServiceId
     */
    function allocateNewService($serviceTypeId, $serviceName, $serviceDescriptor)
    {

        //todo: put this in a transaction to make it safe

        $sql = "insert into logi_Service (service_name,service_type_id,service_descriptor) values('$serviceName',$serviceTypeId,'$serviceDescriptor')";

        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();

        $sql = "select max(service_id) from logi_service ";

        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();

        $newServiceId = $stmt->fetchAll();

        return $newServiceId[0]["MAX(SERVICE_ID)"];
    }


    /*
   * @param string $descriptor : criteria by which the service is identified
   * @return integer serviceId matching discriptor or null (null means this is a new service of such description)
   */
    function findMatchingService($descriptor)
    {

        $sql = $this->getSqlForFindMatchingService($descriptor);

        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();

        $serviceId = $stmt->fetchAll();

        if (isset($serviceId[0]["SERVICE_ID"])) {
            return $serviceId[0]["SERVICE_ID"];
        } else {
            return null;
        }


    }

    /*
     *  Note: this is using pure sql for later more complex scenarios of $discriptor
     */
    function getSqlForFindMatchingService($discriptor)
    {
        $sql = "
          select
                 service_id
          from
                 logi_service
          where
                 service_descriptor = '$discriptor'

          ";

        return $sql;
    }


    function saveServiceRecord($numericHashAsId, $serviceTypeId, $serviceDescriptor, $serviceName)
    {
        $em = $this->_em;
        $logiService = $this->find($numericHashAsId);
        if (!$logiService) {
            $logiService = new LogiService();
            $logiService->setServiceId($numericHashAsId);
            $em->persist($logiService);
        } else {
            $logiService->setServiceId($numericHashAsId);
        }

        $logiService->setServiceTypeId($serviceTypeId);
        $logiService->setServiceDescriptor($serviceDescriptor);
        $logiService->setServiceName($serviceName);


        try {
            $em->flush();
        } catch (\Exception $ex) {
            $message = $ex->getMessage();
            $m = 0;
        }

    }

    function getServiceName($serviceId)
    {
        /* @var $logiService LogiService */
        $logiService = $this->find($serviceId);

        if ($logiService == null) {
            return null;
        }
        return $logiService->getServiceName();
    }

    function removeService($serviceId)
    {

        //todo: put these two executes in a transaction
        //before deleting this service header,make sure you delete all the sub services which are referring to this
        // service

        $sql = "delete from logi_service_segment where segment_log_con_id=$serviceId";
        $stmt = $this->_em->getConnection()->prepare($sql);
        $stmt->execute();


        $sql = "delete from logi_service where service_id=$serviceId";
        $stmt = $this->_em->getConnection()->prepare($sql);

        $stmt->execute();


    }


}