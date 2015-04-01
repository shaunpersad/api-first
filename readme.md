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
`/cats`
`/cats/{cat_id}`
`/users`
`/users/{user_id}`
`/users/{user_id}/cats`
`/users/{user_id}/cats/{cat_id}`


The directory corresponding directory structure for this API would be as follows:

controllers
    |- Api
        |- V1
        |- V2
            |- Abstract
                |- AbstractApiController.php
            |- Resources
                |- Cats
                    |- **CatsController.php**
                |- Users
                    |- Cats
                        |- **UsersCatsController.php**
                    |- **UsersController.php**

libraries
    |-Api
        |- V1
        |- V2
            |- Abstract
                |- AbstractEntityResource.php
                |- AbstractResource.php
            |- Resources
                |- Cats
                    |- **CatsResource.php**
                    |- Users
                        |- Cats
                            |- **UsersCatsResource.php**
                        |- **UsersResource.php**

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

## resource class anatomy

Opening the `CatsResource`, the first thing you'll notice is that it is namespaced `Api/V2`.
If you were to open the corresponding `CatsController`, you'll notice that it is also namespaced `Api/V2`.
We make use of versioned namespaces in our architecture to differentiate between different API versions.
Generally, you will not need to make your namespaces any more specific or go any deeper than `Api/V2`.

Next, `CatsResource` extends `AbstractEntityResource`.  The `Abstract` directory contains both an `AbstractResource`
and an `AbstractEntityResource`.  `AbstractResource` is the all-purpose resource class.

***All*** of your resource classes should be a descendant of `AbstractResource`.

`AbstractEntityResource` on the other hand is made specifically for resources related to your models.
As expected, it extends `AbstractResource`, but adds in functionality specific to handling database models.
Note: in my nomenclature, an "entity" is an instance of a database model, e.g. in `$user = User::find(1)`,
`$user` would be an "entity".


