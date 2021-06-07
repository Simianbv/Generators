# Generators
A generator for Laravel for controllers, models, views and migrations


## Installation

To install, use:

`composer require simianbv/generators`

next up;

`artisan vendor:publish --provider="Simianbv\Generators\GeneratorServiceProvider" --tag=generator-stubs`
`artisan vendor:publish --provider="Simianbv\Generators\GeneratorServiceProvider" --tag=generator-config`

to get the stubs, next up;



## define your YAML

The YAML definitions are ready to be served and used to create models, controllers, resources and 
in the future, filters. 

```yaml
Role:                       # the name of the model
  namespace: Acl            # Define the relative namespace
  timestamps: true          # Allow timestamps for the model
  softDelete: true          # Allow softdeletes
  columns:                  # Define your columns
    id:                     # The name of the column
      type: bigInteger      # The column type
      primary: true         # Whether it's primary
      unsigned: true        # Should be unsigned
    name:                   # The name of the column
      type: string          # The column type
      nullable: false       # Nullable or not
      length: 50            # The length of the column
      unique: true          # Whether it should be unique
  relations:                # Define the relations
    Group:                  # The name of the related Model
      namespace: Acl        # Define the related Model's namespace
      type: belongsToMany   # Define the type of relation
      local: id             # Define the primary key in the related model
      foreign: id           # Define what the foreign key should be 
      
```

Another example:

```yaml
RoleUser:                   # The name of the pivot table
  namespace: Acl            # The relative namespace
  pivot_only: true          # Set to true if this is only a pivt table
  timestamps: true          # Whether you want timestamps
  columns:                  # Define your columns
    id:                     # The name of the column
      type: bigInteger      # The type
      primary: true         # Primary yes or no
      unsigned: true        # Whether is should be unsigned or not
    role_id:                # The name of the column ( note the _id )
      type: bigInteger      # The column type
      nullable: false       # Nullable yes or no
    user_id:                # The name of the column ( note the _id )
      type: bigInteger      # The column type
      nullable: false       # Nullable yes or no
  relations:                # The relations
    Role:                   # The name of the related Model
      namespace: Acl        # The relative namespace this model resides in
      type: hasOne          # The relation type
      local: role_id        # The local key ( note the _id )
      foreign: id           # The foreign table's primary key
    User:                   # The name of the related Model
      namespace:            # The namespace can be left blank
      type: hasOne          # The Relation type
      local: user_id        # The local column
      foreign: id           # The foreign model's column

```

To define the JSON Schema, a Resource class is generated in the `/app/Resources/Json/` directory
where you can define the schema further. You can add layout elements to wrap them inside or align them
horizontally or vertically.  
