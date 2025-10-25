import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\PatientController::remove
* @see app/Http/Controllers/PatientController.php:85
* @route '/patients/{patient}/avatar/remove'
*/
export const remove = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: remove.url(args, options),
    method: 'post',
})

remove.definition = {
    methods: ["post"],
    url: '/patients/{patient}/avatar/remove',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PatientController::remove
* @see app/Http/Controllers/PatientController.php:85
* @route '/patients/{patient}/avatar/remove'
*/
remove.url = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return remove.definition.url
            .replace('{patient}', parsedArgs.patient.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PatientController::remove
* @see app/Http/Controllers/PatientController.php:85
* @route '/patients/{patient}/avatar/remove'
*/
remove.post = (args: { patient: number | { id: number } } | [patient: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: remove.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PatientController::upload
* @see app/Http/Controllers/PatientController.php:68
* @route '/patients/{patient}/avatar/upload'
*/
export const upload = (args: { patient: string | number } | [patient: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(args, options),
    method: 'post',
})

upload.definition = {
    methods: ["post"],
    url: '/patients/{patient}/avatar/upload',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PatientController::upload
* @see app/Http/Controllers/PatientController.php:68
* @route '/patients/{patient}/avatar/upload'
*/
upload.url = (args: { patient: string | number } | [patient: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return upload.definition.url
            .replace('{patient}', parsedArgs.patient.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PatientController::upload
* @see app/Http/Controllers/PatientController.php:68
* @route '/patients/{patient}/avatar/upload'
*/
upload.post = (args: { patient: string | number } | [patient: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(args, options),
    method: 'post',
})

const avatar = {
    remove: Object.assign(remove, remove),
    upload: Object.assign(upload, upload),
}

export default avatar