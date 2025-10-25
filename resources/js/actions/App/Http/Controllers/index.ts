import AuthController from './AuthController'
import UserController from './UserController'
import PatientController from './PatientController'

const Controllers = {
    AuthController: Object.assign(AuthController, AuthController),
    UserController: Object.assign(UserController, UserController),
    PatientController: Object.assign(PatientController, PatientController),
}

export default Controllers