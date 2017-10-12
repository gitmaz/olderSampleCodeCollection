Requirements for Services Module:

0)Assumptions: Service Configuration per equipment or card (device)

-It is assumed that each device type is assigned zero to many service configuration types through invConf type manager.
a service is simply a configuration type having (service type,service id ,interface1 ,interface2)  mandatory field in its XSD.

-the instances of device types (proposed or actual devices) can be configured for compatible services
It should be possible to propose new service (from a list of compatible services) on a device by setting service details (service type, service id and interface1 and interface2 ) through
previously developed logical configuration, instantiated through user filling interface1 ,interface2 id and other details of the service configuration.

-It is assumed that service configuration of each device is automatically detected through NMS and added to the inventory under each device.
Logical configuration tables take care of this.

-each service configuration has details of interfaces which the service is configured for.For example a VLAN with id 12 will have an XML with
attributes (interface1=x, VLANID=12 ,interface2=y)  where x and y are detected through NMS and\or proposed through logical configuration.

-a simple hub device defined on a service (having one input and many outputs) will be configured with as many configuration items as output count
 such as this: (hub with one input and three outputs)
  config item1 : (interface1=hub_input ,VLANID=12 ,interface2=hub_output1)
  config item2 : (interface1=hub_input ,VLANID=12 ,interface2=hub_output2)
  config item3 : (interface1=hub_input ,VLANID=12 ,interface2=hub_output3)

-when configuring an equipment for a service,we will use propose new configuration but will select a configuration that is defined as a service against the equipment type
 the interface assignments will be done automatically by finding a free compatible interface and assigning it to interface1&2 value of service configuration ?
 How to deal with hubs (suggest previous interface1 of prev config as interface1 of this one)?


1) Device Discovery based on Service Configuration
After User configures Equipments (or cards) to be part of a particular service(service types are first defined in Inventory configuration invConf type manager)
and assigned to particular card type or equipment type,then this configuration,is instantiated per physical card or equipments in "Logical Configuration" section by parametrising
each service configuration as a means for defining them for card or equipment).


2) Service Route Recognition

This is an algorithm that works on previous set of equipments(or cards) having a particular service configuration ,and finding the conectivity(physical and logical) for all of those nodes
that poses such services (this sub module should get in a defenition of service and:
 2.1-should be able to first filter those equipments and cards being defined to have such service
 2.2-try to find their connectivity,physical or through other services and put it in an array of "src through dst" format where src and dst are two interfaces that are connected through
 a service or a physical connection
 2.3- build up a graph of such Service Segments useful in presentation of the service

3) Service Route Storage

 The recognised structure should be put on a database with the ability for fast search of any service for later reuse.The structure
 should be optimised for creating a route graph.


4)Service Route Re-composition -Reactive (incremental) versus offline(complete) composition

 4.1-The system should be able to re-assess the service stored structure based on changes on any physical layer or service layer configuration change or the ones introduced
 anew to the Orion(Reactive composition).

 4.2-it should be also feasible to re-create service storage structure from scratch based on physical and virtual topology of the inventory elements


5)  Service Route Presentation
The system should be capable of drawing the graphs of a particular service by selecting the service type from a list of all available services and entering service ID as for defining the service.
This graph will show the connectivity between equipments operating on that service and will show physical connections of those equipments as well as logical connections through sub services of that service.

 The system should be able to zoom in each subservice to view this subservice connectivity of equipments as a new focussed service in a new window.
 The graph will show all the equipments operating on that service either or not they are interconnected with other equipments of same service.If not connected
 ,it will still show the equipment as a visualisation of corrupt operation of the equipment.


END









