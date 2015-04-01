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

the api-first approach is, at its basis, a set of "manager" classes that act as a layer between controllers and business logic.

However, I take this a few steps further.

Firstly, ***everything*** is structured to "look like" an API endpoint.
By ***everything*** I mean from directory structures to the way these classes are called (more on these things later).

Secondly, the power of filtering endpoints is removed from the route filters and placed directly inside of these manager classes.
This allows your classes to be called from anywhere inside your app code while still applying the same level of protection as an http call.

Thirdly, the manager classes are built with inheritance and code reuse in mind.



