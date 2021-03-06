import React, { Component } from 'react';
import { Link, Redirect, withRouter } from 'react-router-dom';
import FlashMessage from 'react-flash-message';
import logo from '../../images/company/logo.jpg';
import '../../styles/Login.css';
class LoginContainer extends Component {
    constructor(props) {
        super(props);
        this.state = {
            isLoggedIn: false,
            error: '',
            formSubmitting: false,
            user: {
                email: '',
                password: '',
            },
            redirect: props.redirect,
        };
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleEmail = this.handleEmail.bind(this);
        this.handlePassword = this.handlePassword.bind(this);
    }
    componentWillMount() {
        let state = localStorage["appState"];
        if (state) {
            let AppState = JSON.parse(state);
            this.setState({ isLoggedIn: AppState.isLoggedIn, user: AppState });
        }
    }
    componentDidMount() {
        const { prevLocation } = this.state.redirect.state || { prevLocation: { pathname: '/dashboard' } };
        if (prevLocation && this.state.isLoggedIn) {
            return this.props.history.push(prevLocation);
        }
    }
    handleSubmit(e) {
        e.preventDefault();
        this.setState({ formSubmitting: true });
        let userData = this.state.user;
        axios.post("api/login", userData).then(response => {
            //console.log(response);
            return response;
        }).then(json => {
            if (json.data.success) {
                let userData = {
                    id: json.data.success.id,
                    name: json.data.success.name,
                    email: json.data.success.email,
                    role: json.data.success.role,
                    access_token: json.data.success.token,
                };
                let appState = {
                    isLoggedIn: true,
                    user: userData
                };
                localStorage["appState"] = JSON.stringify(appState);
                this.setState({
                    isLoggedIn: appState.isLoggedIn,
                    user: appState.user,
                    error: ''
                });
                location.reload()
            }
            else {
                alert(`Our System Failed To Register Your Account!`);
            }
        }).catch(error => {
            if (error.response) {

                let err = error.response.data;
                this.setState({
                    error: err.message,
                    errorMessage: err.errors,
                    formSubmitting: false
                })
            }
            else if (error.request) {

                let err = error.request;
                this.setState({
                    error: err,
                    formSubmitting: false
                })
            } else {

                let err = error.message;
                this.setState({
                    error: err,
                    formSubmitting: false
                })
            }
        }).finally(this.setState({ error: '' }));
    }
    handleEmail(e) {
        let value = e.target.value;
        this.setState(prevState => ({
            user: {
                ...prevState.user, email: value
            }
        }));
    }
    handlePassword(e) {
        let value = e.target.value;
        this.setState(prevState => ({
            user: {
                ...prevState.user, password: value
            }
        }));
    }
    render() {
        const { state = {} } = this.state.redirect;
        const { error } = state;
        return (
            <div className="container login-container">
                <div className="row">
                    <div className="offset-xl-3 col-xl-6 offset-lg-1 col-lg-10 col-md-12 col-sm-12 col-12 login-form-1">
                        <div className="text-center">
                            <img src={logo} />
                            <br></br><br></br>
                            <h5>Inventory System PT. Jaya Timur</h5>
                        </div>
                        <form onSubmit={this.handleSubmit}>
                            <div className="form-group">
                                {this.state.isLoggedIn ? <FlashMessage duration={60000} persistOnHover={true}>
                                    <h5 className={"alert alert-success"}>Login successful, redirecting...</h5></FlashMessage> : ''}
                                {this.state.error ? <FlashMessage duration={100000} persistOnHover={true}>
                                    <h5 className={"alert alert-danger"}>Error: {this.state.error}</h5></FlashMessage> : ''}
                                {error && !this.state.isLoggedIn ? <FlashMessage duration={100000} persistOnHover={true}>
                                    <h5 className={"alert alert-danger"}>Error: {error}</h5></FlashMessage> : ''}
                            </div>
                            <div className="form-group">
                                <input id="email" type="email" name="email" placeholder="E-mail" className="form-control bg-field" required onChange={this.handleEmail} />
                            </div>
                            <div className="form-group">
                                <input id="password" type="password" name="password" placeholder="Password" className="form-control bg-field" required onChange={this.handlePassword} />
                            </div>
                            <button disabled={this.state.formSubmitting} type="submit" name="singlebutton" className=" text-light btnSubmit mb10"> {this.state.formSubmitting ? "Logging You In..." : "Log In"} </button>
                        </form>
                    </div>
                </div>
            </div>
        )
    }
}
export default withRouter(LoginContainer);
