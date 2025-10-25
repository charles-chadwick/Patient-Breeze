import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\UserController::remove
* @see app/Http/Controllers/UserController.php:86
* @route '/users/{user}/avatar/remove'
*/
export const remove = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: remove.url(args, options),
    method: 'post',
})

remove.definition = {
    methods: ["post"],
    url: '/users/{user}/avatar/remove',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\UserController::remove
* @see app/Http/Controllers/UserController.php:86
* @route '/users/{user}/avatar/remove'
*/
remove.url = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { user: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { user: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            user: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        user: typeof args.user === 'object'
        ? args.user.id
        : args.user,
    }

    return remove.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UserController::remove
* @see app/Http/Controllers/UserController.php:86
* @route '/users/{user}/avatar/remove'
*/
remove.post = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: remove.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\UserController::upload
* @see app/Http/Controllers/UserController.php:69
* @route '/users/{user}/avatar/upload'
*/
export const upload = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(args, options),
    method: 'post',
})

upload.definition = {
    methods: ["post"],
    url: '/users/{user}/avatar/upload',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\UserController::upload
* @see app/Http/Controllers/UserController.php:69
* @route '/users/{user}/avatar/upload'
*/
upload.url = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { user: args }
    }

    if (Array.isArray(args)) {
        args = {
            user: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        user: args.user,
    }

    return upload.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UserController::upload
* @see app/Http/Controllers/UserController.php:69
* @route '/users/{user}/avatar/upload'
*/
upload.post = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(args, options),
    method: 'post',
})

const avatar = {
    remove: Object.assign(remove, remove),
    upload: Object.assign(upload, upload),
}

export default avatar