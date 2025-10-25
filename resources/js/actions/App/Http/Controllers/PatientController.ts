import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PatientController::removeAvatar
* @see app/Http/Controllers/PatientController.php:84
* @route '/patients/{patient}/avatar/remove'
*/
export const removeAvatar = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: removeAvatar.url(args, options),
    method: 'post',
})

removeAvatar.definition = {
    methods: ["post"],
    url: '/patients/{patient}/avatar/remove',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PatientController::removeAvatar
* @see app/Http/Controllers/PatientController.php:84
* @route '/patients/{patient}/avatar/remove'
*/
removeAvatar.url = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { patient: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { patient: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            patient: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        patient: typeof args.patient === 'object'
        ? args.patient.id
        : args.patient,
    }

    return removeAvatar.definition.url
            .replace('{patient}', parsedArgs.patient.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PatientController::removeAvatar
* @see app/Http/Controllers/PatientController.php:84
* @route '/patients/{patient}/avatar/remove'
*/
removeAvatar.post = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: removeAvatar.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PatientController::uploadAvatar
* @see app/Http/Controllers/PatientController.php:67
* @route '/patients/{patient}/avatar/upload'
*/
export const uploadAvatar = (args: { patient: string | number } | [patient: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadAvatar.url(args, options),
    method: 'post',
})

uploadAvatar.definition = {
    methods: ["post"],
    url: '/patients/{patient}/avatar/upload',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PatientController::uploadAvatar
* @see app/Http/Controllers/PatientController.php:67
* @route '/patients/{patient}/avatar/upload'
*/
uploadAvatar.url = (args: { patient: string | number } | [patient: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { patient: args }
    }

    if (Array.isArray(args)) {
        args = {
            patient: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        patient: args.patient,
    }

    return uploadAvatar.definition.url
            .replace('{patient}', parsedArgs.patient.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PatientController::uploadAvatar
* @see app/Http/Controllers/PatientController.php:67
* @route '/patients/{patient}/avatar/upload'
*/
uploadAvatar.post = (args: { patient: string | number } | [patient: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadAvatar.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PatientController::index
* @see app/Http/Controllers/PatientController.php:18
* @route '/patients'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/patients',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PatientController::index
* @see app/Http/Controllers/PatientController.php:18
* @route '/patients'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PatientController::index
* @see app/Http/Controllers/PatientController.php:18
* @route '/patients'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PatientController::index
* @see app/Http/Controllers/PatientController.php:18
* @route '/patients'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PatientController::profile
* @see app/Http/Controllers/PatientController.php:51
* @route '/patients/{patient}/profile'
*/
export const profile = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(args, options),
    method: 'get',
})

profile.definition = {
    methods: ["get","head"],
    url: '/patients/{patient}/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PatientController::profile
* @see app/Http/Controllers/PatientController.php:51
* @route '/patients/{patient}/profile'
*/
profile.url = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { patient: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { patient: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            patient: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        patient: typeof args.patient === 'object'
        ? args.patient.id
        : args.patient,
    }

    return profile.definition.url
            .replace('{patient}', parsedArgs.patient.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PatientController::profile
* @see app/Http/Controllers/PatientController.php:51
* @route '/patients/{patient}/profile'
*/
profile.get = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PatientController::profile
* @see app/Http/Controllers/PatientController.php:51
* @route '/patients/{patient}/profile'
*/
profile.head = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PatientController::create
* @see app/Http/Controllers/PatientController.php:28
* @route '/patients/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/patients/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PatientController::create
* @see app/Http/Controllers/PatientController.php:28
* @route '/patients/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PatientController::create
* @see app/Http/Controllers/PatientController.php:28
* @route '/patients/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PatientController::create
* @see app/Http/Controllers/PatientController.php:28
* @route '/patients/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PatientController::store
* @see app/Http/Controllers/PatientController.php:42
* @route '/patients/store'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/patients/store',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PatientController::store
* @see app/Http/Controllers/PatientController.php:42
* @route '/patients/store'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PatientController::store
* @see app/Http/Controllers/PatientController.php:42
* @route '/patients/store'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PatientController::edit
* @see app/Http/Controllers/PatientController.php:57
* @route '/patients/edit/{patient}'
*/
export const edit = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/patients/edit/{patient}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PatientController::edit
* @see app/Http/Controllers/PatientController.php:57
* @route '/patients/edit/{patient}'
*/
edit.url = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { patient: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { patient: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            patient: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        patient: typeof args.patient === 'object'
        ? args.patient.id
        : args.patient,
    }

    return edit.definition.url
            .replace('{patient}', parsedArgs.patient.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PatientController::edit
* @see app/Http/Controllers/PatientController.php:57
* @route '/patients/edit/{patient}'
*/
edit.get = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PatientController::edit
* @see app/Http/Controllers/PatientController.php:57
* @route '/patients/edit/{patient}'
*/
edit.head = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PatientController::update
* @see app/Http/Controllers/PatientController.php:59
* @route '/patients/update/{patient}'
*/
export const update = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(args, options),
    method: 'post',
})

update.definition = {
    methods: ["post"],
    url: '/patients/update/{patient}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PatientController::update
* @see app/Http/Controllers/PatientController.php:59
* @route '/patients/update/{patient}'
*/
update.url = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { patient: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { patient: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            patient: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        patient: typeof args.patient === 'object'
        ? args.patient.id
        : args.patient,
    }

    return update.definition.url
            .replace('{patient}', parsedArgs.patient.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PatientController::update
* @see app/Http/Controllers/PatientController.php:59
* @route '/patients/update/{patient}'
*/
update.post = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PatientController::destroy
* @see app/Http/Controllers/PatientController.php:61
* @route '/patients/delete/{patient}'
*/
export const destroy = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: destroy.url(args, options),
    method: 'get',
})

destroy.definition = {
    methods: ["get","head"],
    url: '/patients/delete/{patient}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PatientController::destroy
* @see app/Http/Controllers/PatientController.php:61
* @route '/patients/delete/{patient}'
*/
destroy.url = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { patient: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { patient: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            patient: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        patient: typeof args.patient === 'object'
        ? args.patient.id
        : args.patient,
    }

    return destroy.definition.url
            .replace('{patient}', parsedArgs.patient.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PatientController::destroy
* @see app/Http/Controllers/PatientController.php:61
* @route '/patients/delete/{patient}'
*/
destroy.get = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: destroy.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PatientController::destroy
* @see app/Http/Controllers/PatientController.php:61
* @route '/patients/delete/{patient}'
*/
destroy.head = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: destroy.url(args, options),
    method: 'head',
})

const PatientController = { removeAvatar, uploadAvatar, index, profile, create, store, edit, update, destroy }

export default PatientController