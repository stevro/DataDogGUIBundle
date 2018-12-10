# data-dog-gui-bundle
GUI for Symfony 2 DataDog Audit Bundle


# Install
Run: composer require stev/data-dog-audit-gui-bundle

DataDog Audit Bundle will be installed if doesn't exist yet in your project.
https://github.com/DATA-DOG/DataDogAuditBundle

FOS JS Routing Bundle will be installed if doesn't exist yet in your project.
https://github.com/FriendsOfSymfony/FOSJsRoutingBundle

Make sure to read their documentation and enable their bundles in your AppKernel.php

# Configure

Add routing in routing.yml:

    StevDataDogAuditGUIBundle:
      resource: "@StevDataDogAuditGUIBundle/Controller/"
      type:     annotation
      prefix:   /audit
  
It's a good idea to secure the audit in security.yml:

    access_control:
        ...
        - { path: ^/audit, role: [ROLE_SUPER_ADMIN] }
        ...
        
Run assets:install and then add the following JS in your HTML:
"bundles/stevdatadogauditgui/audit.js"

# How to use it
You can use the provided GUI by calling the following function from your JS.

     StevDataDogAuditGUI.openEntityAuditLogs(slot.id, 'SmartTimeslotBundle:Event', [], true);
     
It will open a modal with the audit logs displayed in a paged table.
REQUIRES jQuery and jQuery DataTables.

You can use the service 'stev_data_dog_audit_gui.audit_reader' from your controllers:

    /* @var $auditReader \Stev\DataDogAuditGUIBundle\Services\AuditReader */
    $auditReader = $this->get('stev_data_dog_audit_gui.audit_reader');
    $audit = $auditReader->getAuditForEntity($entityId, $entityClass, $assocsToInclude, $includeInserts);
    
And you can display them using a similar logic to the one in Stev\DataDogAuditGUIBundle\Resources\Views\Audit\entity.html.twig
