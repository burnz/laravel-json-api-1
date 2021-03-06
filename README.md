# JSON Api

JSON Api Package for Laravel

[![Latest Stable Version](https://poser.pugx.org/ipunkt/laravel-json-api/v/stable.svg)](https://packagist.org/packages/ipunkt/laravel-json-api) [![Latest Unstable Version](https://poser.pugx.org/ipunkt/laravel-json-api/v/unstable.svg)](https://packagist.org/packages/ipunkt/laravel-json-api) [![License](https://poser.pugx.org/ipunkt/laravel-json-api/license.svg)](https://packagist.org/packages/ipunkt/laravel-json-api) [![Total Downloads](https://poser.pugx.org/ipunkt/laravel-json-api/downloads.svg)](https://packagist.org/packages/ipunkt/laravel-json-api)


## Installation

```shell
composer require ipunkt/laravel-json-api
```

The package supports the package auto-discovery feature since laravel 5.5. For older laravel versions follow the instructions:

Add service provider to `config/app.php`:
```php
'providers' => [
	\Ipunkt\LaravelJsonApi\LaravelJsonApiServiceProvider::class,
]
```

Add the following Facades to your `config/app.php` file:

```php
'RelationshipFilterParser' => \Ipunkt\LaravelJsonApi\Services\RelationshipFilterParser\RelationshipFilterParserFacade::class,
'FilterApplier' => \Ipunkt\LaravelJsonApi\Services\FilterApplier\FilterApplierFacade::class,
```

### Middleware

We provide several middlewares `Ipunkt\LaravelJsonApi\Http\Middleware\ContentTypeGuard` and `Ipunkt\LaravelJsonApi\Http\Middleware\ETagMiddleware` and `Ipunkt\LaravelJsonApi\Http\Middleware\GetUserFromToken`.

First checks that `content-type` and `accept` header will have the correct value and exists. Second handles providing the correct `ETag` response header to support your cache infrastructure. Last one overrides the error response for the JWT Authentication middleware.

Set the necessary middleware in `app/Http/Kernel.php` in the `$routeMiddleware` section like this:
```php
'jwt.auth' => \Ipunkt\LaravelJsonApi\Http\Middleware\GetUserFromToken::class,
'jwt.refresh' => \Tymon\JWTAuth\Middleware\RefreshToken::class,

'api-content-type' => \Ipunkt\LaravelJsonApi\Http\Middleware\ContentTypeGuard::class,
'etag' => \Ipunkt\LaravelJsonApi\Http\Middleware\ETagMiddleware::class,
```

Please follow the installation instructions documented [here](https://github.com/tymondesigns/jwt-auth/wiki/Installation) for the `tymon/auth` package.

For the api routes we need middleware groups named `api` and `secure-api`. These can be configured like this:

```php
/**
 * The application's route middleware groups.
 *
 * @var array
 */
protected $middlewareGroups = [
	'api' => [
		'api-content-type',
		'etag',
	],

	'secure-api' => [
		'api-content-type',
		'etag',
	],
];
```

## Configuration

By default the package configures all routes itself. This is the suggested option.

You can configure the json api responses as well. There are optional response elements in the json api 1.0 standard. By default we return them all, but you can turn them off if you want to save response bytes.

Publish Configuration (optional step, but suggested):
```shell
php artisan vendor:publish --provider="Ipunkt\LaravelJsonApi\LaravelJsonApiServiceProvider"
```

### defaults section

#### max-limits

How many results should be returned by default (no `page[limit]` query parameter set). We have `50` by default.

### routes section

#### configure

Do you want the routes being configured by the package itself? Leave it true in most cases.

#### public-route

We have public and secure routes by default in the package. Public routes do not check authentication. This is for authenticating an user or public accessible api endpoints.

Here you can configure the `prefix` for the route and the `controller` for handling requests.

#### secure-route

Secure routes check authentication with each request. Here you need a JWT access token for accessing these resources.

Here you can configure the `prefix` for the route and the `controller` for handling requests. You have also the option to define `middleware`. A `jwt.auth` called middleware is configured by default.

### response section

#### resources

Resources can have a links section. Shall the package add the self link automatically? It will be added by default, but it is not necessary in every case.

Resource items can have a self link too. This will be added automatically by the package by default. You can turn that off if you do not need it.

#### relationships

Relationships and the items itself can have a links section with the self and related link. These can all be added automatically by the package. You can turn that off, if you do not need it.

## Definition

### Setup

We suggest using the `app/Providers/AppServiceProvider` or create your own `ApiResourceServiceProvider`.

Customize your `boot` method to type hint the `\Ipunkt\LaravelJsonApi\Resources\ResourceManager` as parameter, like so:

```php
public function boot(ResourceManager $resourceManager)
```

### Define a resource

The `JsonApiController` handles the incoming api request. It uses the `ResourceManager` to get to know all defined resources. So define your resources like so:

```php
//  define in api version 1 a resource called 'posts'
$resourceManager->version(1)
    ->define('posts', function (ResourceDefinition $resource) {
        $resource->setRepository(PostRepository::class)
            ->setSerializer(PostSerializer::class);
    });
```
Each Api has various versions, 1 at minimum.

For each version you can define resources (URL `/public/v1/posts`) and as a callback you can define various types: at least a repository for fetching the resource models and a serializer to transform the fetched model data into your wanted format.

Additionally you can define a custom request handler. There you can process the whole request yourself until returning the response. So you have full control.

The Json Api Standard has various filter options. We have a Filter Factory to support this kind of layer. A Filter Factory handles the given filter and sets it to the repository by default. So you can filter by attribute or search within a time period through request parameters. If you want to use filter you have to define a filter factory. Otherwise your filters will not be applied.

If you want to use a secure api route, you have to setup a request handler as well:
```php
//  define in api version 1 a resource called 'posts'
$resourceManager->version(1)
    ->define('posts', function (ResourceDefinition $resource) {
        $resource->setRepository(PostRepository::class)
            ->setSerializer(PostSerializer::class)
            ->setRequestHandler(PostRequestHandler::class);
    });
```


### Implement a Serializer

```php
class PostSerializer extends \Ipunkt\LaravelJsonApi\Serializers\Serializer
{
	/**
	 * resource type in response, can differ from requesting resource name
	 * @var string
	 */
	protected $type = 'posts';

	/**
     * returns links
     *
     * @param Model|Post $model
     * @return array
     */
    public function getLinks($model) : array
    {
        return [
            'comments' => 'https://localhost/api/v1/posts/1/comments',
        ];
    }

    /**
     * returns attributes for model
     *
     * @param Model|Post $model
     * @return array
     */
    protected function attributes($model) : array
    {
        return [
            'title' => $model->title,
            'slug' => str_slug($model->title),
            'content' => $model->content,
            'excerpt' => substr($model->content, 0, 200),
            'words' => count(explode(' ', $model->content)), //  example to show you can return more than only concrete model attributes
        ];
    }
}
```

### Implement a Repository

Follows the repository pattern and stores default sort criteria and a mapping for parameter request to database field name.

```php
class PostRepository extends \Ipunkt\LaravelJsonApi\Repositories\Repository
{
	/**
     * default sort criteria, when nothing given (can be empty)
     *
     * Format: 'fieldName' => 'asc', // or 'desc'
     *
     * @var array
     */
    protected $defaultSortCriterias = [
        'publish_datetime' => 'desc'
    ];

    /**
     * sort criterias (can be empty)
     *
     * Format: 'attributeNameInRequest' => 'field_name_in_database'
     * Example: 'date' in request will be 'publish_datetime' in sql query
     *
     * @var array
     */
    protected $sortCriterias = [
        'date' => 'publish_datetime',
    ];

    /**
     * constructor.
     * @param Model|Post $post
     * @param \Ipunkt\LaravelJsonApi\Repositories\Conditions\ConditionApplier $conditionApplier
     */
    public function __construct(Post $post, \Ipunkt\LaravelJsonApi\Repositories\Conditions\ConditionApplier $conditionApplier)
    {
        $this->model = $post;
        $this->conditionApplier = $conditionApplier;
    }
}
```

### Implement a Request Handler

We provide a request handler for handling retrieval requests (GET): `DefaultRequestHandler`.

The `\Ipunkt\LaravelJsonApi\Contracts\RequestHandlers\NeedsAuthenticatedUser` interface controls that your resource can be accessed via the secure route. Without you have to use the public route.

```php
class PostRequestHandler extends \Ipunkt\LaravelJsonApi\Http\RequestHandlers\DefaultRequestHandler implements NeedsAuthenticatedUser
{

}
```

If you need more freedom then create a request handler yourself and inherit from `Ipunkt\LaravelJsonApi\Http\RequestHandlers\RequestHandler`. The whole configured actions will be provided by various interfaces in the namespace `Ipunkt\LaravelJsonApi\Contracts\RequestHandlers`: `HandlesCollectionRequest`, `HandlesItemRequest`, `HandlesRelationshipCollectionRequest`, `HandlesRelationshipItemRequest` and the modifiable interfaces `HandlesPostRequest`, `HandlesPatchRequest`, `HandlesDeleteRequest` and the relationship interfaces as well. Take a look yourself.

You can simplify the data modification requests by using one of the delivered traits within your own request handler. The traits can be found under `Ipunkt\LaravelJsonApi\Http\RequestHandlers\Traits`. Take a look yourself.

## Error Handling in Laravel

Simply extend your `app/Exceptions/Handler.php` in the following way:

Method `render()` should extend with this code:

```php
if ($request->expectsJson() ||
	$request->headers->contains('accept', ApiRequestHandler::CONTENT_TYPE)) {
	$error = new JsonApiError($exception->getMessage());

	if ($exception->getCode() > 100) {
		$error->setCode($exception->getCode());
	}

	if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
		$error->setStatusCode(404)
			->setTitle('Resource not found');
	}

	if ($exception instanceof AuthorizationException) {
		$error->setStatusCode(403)
			->setTitle('Access forbidden');
	}

	if ($exception instanceof ValidationException) {
		$validationErrors = collect();
		foreach ($exception->validator->errors()->keys() as $key) {
			$validationErrors->push([
				'pointer' => $key,
				'message' => str_replace('attributes.', '', $exception->validator->errors()->first($key)),
			]);
		}
		$error->setSource($validationErrors);
	}

	if ($exception instanceof HttpExceptionInterface) {
		$error->setStatusCode($exception->getStatusCode());
	}

	if (app()->environment('local')) {
		$error->setException($exception);
	}

	return response()->json(['errors' => [$error]], $error->getStatusCode());
}
```
Extend your special exceptions to match the correct code and status code. Code means the Exception code and status code is the http status response code.

Method `unauthenticated()` should extend with this code:

```php
if ($request->expectsJson() ||
	$request->headers->contains('accept', ApiRequestHandler::CONTENT_TYPE)) {
	$error = new JsonApiError('Unauthenticated');
	$error->setStatusCode(401);

	return response()->json(['errors' => [$error]], $error->getStatusCode());
}
```

## Test

We provide a trait for adding functionality for json api testing in the `tests/TestCase.php`: `\Ipunkt\LaravelJsonApi\Testing\ApiTestCaseTrait`.

This TestCase trait is optimized for testing with Laravel 5.3 or Laravel 5.4 BrowserKitTest.

### Laravel 5.4

Since Laravel 5.4 has a lot of convenient methods for requesting and asserting you only need the `Ipunkt\LaravelJsonApi\Testing\Concerns\PreparesRequestBody` trait. Simply add it to your `Tests\TestCase` use statements.

The `PreparesRequestBody` provides model to request body transformation methods.

If you want to provide some kind of login or secure api, then you have to add `Ipunkt\LaravelJsonApi\Testing\Concerns\ModifiesRequestHeaders` to your `Tests\TestCase` or wherever you want to store a token. This trait provides token storing and a `->headers()` method to overwrite the headers with the bearer token.

#### Example User Login

If you want to create a user login to test a secure api route you can do it like this:
```php
namespace Tests;

use App\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Ipunkt\LaravelJsonApi\Testing\Concerns\ModifiesRequestHeaders;
use Ipunkt\LaravelJsonApi\Testing\Concerns\PreparesRequestBody;

abstract class TestCase extends BaseTestCase
{
	use PreparesRequestBody,
		ModifiesRequestHeaders;
	use CreatesApplication;

	/**
	 * creates a user and logs him in
	 *
	 * @return User
	 */
	protected function createUserAndLogin(): User
	{
		$user = factory(\App\User::class)->create();

		$response = $this->postJson('/public/v1/tokens', $this->createRequestModel('credentials', [
			'email' => $user->email,
			'password' => 'secret',
		]), $this->headers());

		$json = $response->decodeResponseJson();
		$token = array_get($json, 'data.id');

		$this->setToken($token); // you are loggedin

		// you can now overwrite all requests headers with calling $this->headers().

		return $user;
	}
}
```

This code creates a new user within the database, fetches a token (stored in response `data.id`) and sets it to a static variable. Afterwards you can use the `$this->headers()` method for every request to overwrite the necessary headers.
