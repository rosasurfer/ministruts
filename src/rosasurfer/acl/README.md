
                                              Access Control Management
                                             ===========================

 - Terminology
   • Resource:    identity object under access control
   • Task:        combination of a specific operation on one or many specific resources
   • AccessMode:  generic term for grant or revokation of a right 
   • Privilege:   right granting AccessMode   
   • Restriction: right revoking AccessMode 
   • Permission:  Privilege to execute a Task
   • Denial:      prohibition (Restriction) to execute a Task
   • Policy:      generic term for Permission or Denial to execute a Task (optional with additional conditions) 
   • Role:        set of Policies or other Roles (optional with additional conditions)
   • Attribute:   boolean condition controling activation of a Role or Policy (e.g. in a time or location context)
   • Actor:       identity subject requesting access to a Resource to execute a Task  
  
 - Roles and Policies can be assigned to Actors and groups of Actors.

 - Policies can be aggregated to Roles.
 
 - Roles can be mutually exclusive (in a given context at the same time).   
 
 - Difference between Roles and Groups
   • Roles organize activities, groups organize identities.  
   • Typically a group membership remains during the duration of a login. Roles and permissions, on the other hand, can be 
     activated according to specific conditions (e.g. by time of day, location of access).
   • For the usage of classifying things together, groups and roles function similar. Groups, however, are based on identity 
     whereas roles are meant to demarcate activity.
   • Actors and Resources are both identities. Thus both can be organized in groups. 

 - A Resource can become an Actor, an Actor can become a Resource. Both can be converted as required. An identity can be both 
   Actor (subject) and Resource (object) at the same time (e.g. a user updating his user profile).
   