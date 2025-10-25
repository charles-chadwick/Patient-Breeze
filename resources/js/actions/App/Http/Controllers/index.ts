import AuthController from './AuthController'
import UserController from './UserController'

const Controllers = {
    AuthController: Object.assign(AuthController, AuthController),
    UserController: Object.assign(UserController, UserController),
}

export default Controllers