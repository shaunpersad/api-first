## api-first

api-first is an architecture proposal for building applications with an API-centric mindset.

This particular implementation is built with Laravel in mind, though the concepts can be easily (in theory) ported over to other frameworks and languages.

## the old way

Creating APIs in Laravel can be trivial.  You've got all of your basic tools (in order of execution):
 - route filters
 - routes
 - controllers
 - models

Using these tools, typically the fastest way to get your API up and running is to implement authorization and authentication
using route filters, then stick the business logic in the controllers, calling the database models as necessary.

The end result is certainly a ***functional*** API, but not a very flexible one.

For instance, what if I need to make an API call somewhere internally in my app code?
What if I have two endpoints that share almost exactly the same type of requests and responses?
(e.g. `/cats` vs. `/users/{user_id}/cats`)

## the "new" way

the api-first approach is, at its basis, a set of "manager" resource classes that act as a layer between controllers and business logic.

However, I take this a few steps further.

Firstly, ***everything*** is structured to "look like" an API endpoint.
By ***everything*** I mean from directory structures to the way these classes are called (more on these things later).

Secondly, the power of filtering endpoints is removed from the route filters and placed directly inside of these resource classes.
This allows your classes to be called from anywhere inside your app code while still applying the same level of protection as an http call.

Thirdly, the resource classes are built with inheritance and code reuse in mind.

## directory structure

Both the controllers and resource classes follow a directory structure that attempt to mirror the actual API endpoints that they represent.

For example, let us consider an API with the following endpoints:

 - `/cats`
 - `/cats/{cat_id}`
 - `/users`
 - `/users/{user_id}`
 - `/users/{user_id}/cats`
 - `/users/{user_id}/cats/{cat_id}`


The directory corresponding directory structure for this API would be as follows:

controllers: https://www.dropbox.com/s/gznhg6oh7ie8hg5/Screenshot%202015-04-01%2008.57.23.png?dl=0

libraries: https://www.dropbox.com/s/65iyv71ef9mbuo7/Screenshot%202015-04-01%2008.56.20.png?dl=0

There are a few things to notice here.

Firstly, the `libraries` directory is simply a directory to put your custom application-specific code.
It doesn't have to be named as such, but it is merely my convention.

Secondly, notice that the `controllers` directory structure almost matches the `libraries` directory structure exactly.

Starting from the root of each, we have an `Api` directory. This stores our Api-specific code.
This comes in handy when your application is not just an API.  Perhaps it also serves a CMS to manage the API data,
in which case, you'd also ideally have a `controllers/Cms` directory to put your CMS controllers,
and a `libraries/Cms` directory to put your CMS-specific code.

Next, we have the `V1` and `V2` directories.  These help version our API.
(Note: for the purposes of this example, V1 is absent.)

Inside of `V2`, we have `Abstract` and `Resources`.

Our architecture makes use of abstract parent classes in order to encourage inheritance of helper methods,
and to take care of a lot of the set-up that goes into using a resource effectively with maximum code reuse.
The `Abstract` directory is where you'd put these classes.

The `Resources` directory houses the actual controllers for each resource in the `controllers` directory,
and the actual manager resource classes in the `libraries` directory.

You will notice, for example, that in `Resources` there are the `Cats` and `Users` directories.
The directories immediately following `Resources` should generally always map to your models.
This sets up "base" resources for "base" endpoints.
Generally you'll want to have an endpoint for each model, so your directory structure should match that.
Even if all those base endpoints are not publicly exposed, it is still a good idea to have base endpoints built out, but protected.

Inside of `Users` in the `libraries` directory, you'll notice a `UsersResource` class, and another `Cats` directory.

The `UsersResource` class is where you'd implement the actual manager class for the base `/users` endpoint (more on that later).
The `Users/Cats/UsersCatsResource` on the other hand extends from the base `Cats/CatsResource` manager class.
The difference between the `CatsResource` and the `UsersCatsResource` is that `CatsResource` is the base class.
It handles the endpoints of `/cats` and `/cats/{cat_id}`,
whereas `UsersCatsResource` handles the endpoints for user-specific cats `/users/{user_id}/cats` and `/users/{user_id}/cats/{cat_id}`.

Why bother to create two different classes to handle cats when one can simply extend one from the other?

## resource class anatomy (reference CatsResource.php)

### namespace
Opening the `CatsResource`, the first thing you'll notice is that it is namespaced `Api/V2`.
If you were to open the corresponding `CatsController`, you'll notice that it is also namespaced `Api/V2`.
We make use of versioned namespaces in our architecture to differentiate between different API versions.
Generally, you will not need to make your namespaces any more specific or go any deeper than `Api/V2`.

### parent classes
Next, `CatsResource` extends `AbstractEntityResource`.  The `Abstract` directory contains both an `AbstractResource`
and an `AbstractEntityResource`.  `AbstractResource` is the all-purpose resource class.

***All*** of your resource classes should be a descendant of `AbstractResource`.

`AbstractEntityResource` on the other hand is made specifically for resources related to your models.
As expected, it extends `AbstractResource`, but adds in functionality specific to handling database models.
Note: in my nomenclature, an "entity" is an instance of a database model, e.g. in `$user = User::find(1)`,
`$user` would be an "entity".

### the "with" property
Up next in the `CatsResource` anatomy is a protected property called `$with`. Much like the Eloquent function,
this allows us to load specified associations whenever we ask for an entity, e.g. with a `get()` call (to be explained soon).
This association loading can even be applied to collections of entities if we so desire (also to be explained soon).

### endpoint filtering
Following this is a public static method called `endpointFilters`. This is a highly important method.
This is where we can define filters for our endpoints. Furthermore, all endpoint methods that are not public
***must*** have a filter defined in this method, or else it will not be callable. Another ramification of this restriction
is that every resource class that contains protected endpoint methods must implement an `endpointFilters` static method.

The `AbstractResource` argument in `endpointFilters` will always be the resource class that is being filtered.
It is not necessary to use it (and in the example, it isn't), but it serves as a reference, just in case it is needed.

`endpointFilters` always returns an array whose keys are the names of the endpoint methods you wish to filter.
The values of this array are either string constants defined in the `Api` class, or an anonymous function.
Either way, a function is called that filters access to the endpoint.
It does this by either throwing an `ApiException` or not, which is a much more robust and informative way of
filtering, vs. simply returning `true` or `false`.

You will notice that `CatsResource` has "all", "create", and "get" endpoint methods being filtered.
"all" and "create" are defined in `CatsResource`, but "get" is inherited from `AbstractEntityResource`.
"get" is a method that returns the entity (along with the associations specified in `with`), e.g.
`/cats/{cat_id}` will result in a call to the "get" method of `CatsResource`. Since it is a fair assumption to say
that every entity-based resource of your API will want to return its entity, "get" is already included in `AbstractEntityResource`.

As a matter of convention, methods named "all" should return a collection. It should be the method called
when making an API call to the "base" of the resource, e.g. `/cats`. This method was not included in `AbstractEntityResource`
since implementation of this type of behavior can vary wildly.

You may notice that the actual endpoint methods in our resource classes are `protected`.
As you know, `protected` methods cannot ordinarily be accessed. This is where `endpointFilters` kicks in.

`endpointFilters` is called whenever a `protected` method is called in a resource class.
It then runs the corresponding filter function for the method called,
and if the filter function exists and no errors are thrown, it allows access to that endpoint method.

### the base query
Next in the `CatsResource` anatomy is the `query` public method. This is another important method that must be
defined when extending `AbstractEntityResource`. It specifies the base Eloquent/Fluent query that will be used when
calling methods such as "all". As such, in the "all" method, you should use this as the base of the query
(by calling `$this->query()` instead of calling `Cats::`. This simple requirement helps tremendously with resource class extension.

For example, `UsersCatsResource` extends `CatsResource`, with the only overridden methods being `query` and `create`.
This allows us to use the exact same "all" method from `CatsResource`, but with a base query that is restricted to only cats belonging to a particular user.

### endpoint param validation
In the "all" method of `CatsResource`, you will see this strange setup:
```
$defaults = array(
    'with' => $with = null,
    'order_by' => $order_by = 'users.created_at',
    'order_dir' => $order_dir = 'DESC',
    'page' => $page = 1,
    'per_page' => $per_page = 30
);

$params = $this->validateParams($defaults, $params);

extract($params);
```

Firstly, we are creating a `$defaults` array, but defining variables while assigning them to the array.
This is done because as you will notice at the end of the snippet, we are extracting the variables from the array.
By defining the variables, you may safely call for example `$order_by` later in the method, knowing it exists.
As a bonus, IDEs will not complain about using variables that have not been initialized.
