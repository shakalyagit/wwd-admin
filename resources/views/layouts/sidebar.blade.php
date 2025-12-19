<style>
    .profile-upload-wrapper {
        position: relative;
        width: 468px;
        border: 1px dashed;
        padding: 10px 10px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .profile-preview {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background-color: #f1f1f1;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        overflow: hidden;
        object-fit: cover;
    }

    .profile-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    #removeImage {
        position: absolute;
        top: -10px;
        right: -10px;
        border-radius: 50%;
        border: 1px dashed #000;
    }
</style>
<nav class="navbar navbar-light navbar-vertical navbar-expand-xl navbar-vibrant" style="display: none;">
    <div class="d-flex align-items-center logo_nav">
        <div class="toggle-icon-wrapper">
            <button class="btn navbar-toggler-humburger-icon navbar-vertical-toggle" data-bs-toggle="tooltip"
                data-bs-placement="left" title=""><span class="navbar-toggle-icon"><span
                        class="toggle-line"></span></span></button>
        </div>
        <a class="navbar-brand" href="/">
            <div class="d-flex align-items-center ">
                <img class="me-2" src="/assets/img/icons/spot-illustrations/wwd.png" alt="" width="40" />
                <span class="font-sans-serif text-dark" style="font-size:12px;">for business</span>
            </div>
        </a>
    </div>
    <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <div class="navbar-vertical-content scrollbar">
            <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav">
                <li class="nav-item">
                    <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                        <div class="col-auto navbar-vertical-label">Module</div>
                        <div class="col ps-0">
                            <hr class="mb-0 navbar-vertical-divider" />
                        </div>
                    </div>
                </li>
                <li>
                    <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }} || {{ request()->is('dashboard-2') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}" role="button">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon">
                                <span class="bi bi-speedometer2"></span>
                            </span>
                            <span class="nav-link-text ps-1">Dashboard</span>
                        </div>
                    </a>
                </li>
                <li>
                    <a class="nav-link {{ request()->is('business-list') ? 'active' : '' }} || {{ request()->is('edit-business/*') ? 'active' : '' }}"
                        href="{{ route('business_list') }}" role="button">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon">
                                <span class="bi-shield-lock"></span>
                            </span>
                            <span class="nav-link-text ps-1">Business Listing</span>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<nav class="navbar navbar-light navbar-glass navbar-top navbar-expand-lg" style="display: none;">
    <div class="d-flex align-items-center">
        <div class="toggle-icon-wrapper">
            <button class="btn navbar-toggler-humburger-icon navbar-vertical-toggle" data-bs-toggle="tooltip"
                data-bs-placement="left" title="Toggle Navigation"><span class="navbar-toggle-icon"><span
                        class="toggle-line"></span></span></button>
        </div>
        <a class="navbar-brand" href="/">
            <div class="d-flex align-items-center py-3">
                <img class="me-2" src="/assets/img/icons/spot-illustrations/wwd.png" alt=""
                    width="60" />
            </div>
        </a>
    </div>
    <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <div class="navbar-vertical-content scrollbar">
            <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav">
                <li class="nav-item"><!-- label-->
                    <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                        <div class="col-auto navbar-vertical-label">Module</div>
                        <div class="col ps-0">
                            <hr class="mb-0 navbar-vertical-divider" />
                        </div>
                    </div>
                    <a class="nav-link" href="{{ route('dashboard') }}" role="button">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon">
                                <span class="fas fa-chart-pie"></span>
                            </span>
                            <span class="nav-link-text ps-1">Dashboard</span>
                        </div>
                    </a>
                    <a class="nav-link" href="app/calendar.html" role="button">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span
                                    class="fas fa-calendar-alt"></span></span><span
                                class="nav-link-text ps-1">Calendar</span></div>
                    </a><!-- parent pages--><a class="nav-link" href="app/chat.html" role="button">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span
                                    class="fas fa-comments"></span></span><span class="nav-link-text ps-1">Chat</span>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="content">
    <nav class="navbar navbar-light navbar-glass navbar-top navbar-expand" style="display: none;">
        <button class="btn navbar-toggler-humburger-icon navbar-toggler me-1 me-sm-3" type="button"
            data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse"
            aria-expanded="false" aria-label="Toggle Navigation"><span class="navbar-toggle-icon"><span
                    class="toggle-line"></span></span></button>
        <a class="navbar-brand me-1 me-sm-3" href="{{ route('dashboard') }}">
            <div class="d-flex align-items-center"><img class="me-2"
                    src="/assets/img/icons/spot-illustrations/wwd.png" alt="" width="40" /><span
                    class="font-sans-serif text-primary"></span></div>
        </a>
        <ul class="navbar-nav navbar-nav-icons ms-auto flex-row align-items-center">
            <span class="text-600" style="font-size:14px;">{{ auth()->user()->name }}
                <p class="text-400" style="font-size: 12px; margin-bottom: 0px;"></p>
            </span>
            <li class="nav-item dropdown">
                <!-- <a class="nav-link notification-indicator notification-indicator-primary px-0 fa-icon-wait" id="navbarDropdownNotification" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-hide-on-body-scroll="data-hide-on-body-scroll"><svg class="svg-inline--fa fa-bell fa-w-14" data-fa-transform="shrink-6" style="font-size: 33px;transform-origin: 0.4375em 0.5em;" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="bell" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg="">
                        <g transform="translate(224 256)">
                            <g transform="translate(0, 0)  scale(0.625, 0.625)  rotate(0 0 0)">
                                <path fill="currentColor" d="M224 512c35.32 0 63.97-28.65 63.97-64H160.03c0 35.35 28.65 64 63.97 64zm215.39-149.71c-19.32-20.76-55.47-51.99-55.47-154.29 0-77.7-54.48-139.9-127.94-155.16V32c0-17.67-14.32-32-31.98-32s-31.98 14.33-31.98 32v20.84C118.56 68.1 64.08 130.3 64.08 208c0 102.3-36.15 133.53-55.47 154.29-6 6.45-8.66 14.16-8.61 21.71.11 16.4 12.98 32 32.1 32h383.8c19.12 0 32-15.6 32.1-32 .05-7.55-2.61-15.27-8.61-21.71z" transform="translate(-224 -256)"></path>
                            </g>
                        </g>
                    </svg>
                </a> -->
                <div class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end dropdown-menu-card dropdown-menu-notification dropdown-caret-bg"
                    aria-labelledby="navbarDropdownNotification">
                    <div class="card card-notification shadow-none">
                        <div class="card-header">
                            <div class="row justify-content-between align-items-center">
                                <div class="col-auto">
                                    <h6 class="card-header-title mb-0">Notifications</h6>
                                </div>
                            </div>
                        </div>
                        <div class="scrollbar-overlay" style="max-height:19rem" data-simplebar="init">
                            <div class="simplebar-wrapper" style="margin: 0px;">
                                <div class="simplebar-height-auto-observer-wrapper">
                                    <div class="simplebar-height-auto-observer"></div>
                                </div>
                                <div class="simplebar-mask">
                                    <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                        <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                            aria-label="scrollable content" style="height: auto; overflow: hidden;">
                                            <div class="simplebar-content" style="padding: 0px;">
                                                <div class="list-group list-group-flush fw-normal fs-10">
                                                    <div class="list-group-title border-bottom"></div>
                                                    <div class="list-group-item">
                                                        <a class="notification notification-flush notification-unread"
                                                            href="#!">
                                                            <div class="notification-avatar">
                                                                <div class="avatar avatar-2xl me-3">
                                                                    <img class="rounded-circle"
                                                                        src="/assets/img/team/1-thumb.png"
                                                                        alt="">
                                                                </div>
                                                            </div>
                                                            <div class="notification-body">
                                                                <p class="mb-1"><strong>Emma Watson</strong> replied
                                                                    to your comment : "Hello world"</p>
                                                                <span class="notification-time"><span class="me-2"
                                                                        role="img"
                                                                        aria-label="Emoji">💬</span>Just now</span>
                                                            </div>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="simplebar-placeholder" style="width: 0px; height: 0px;"></div>
                            </div>
                            <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                                <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
                            </div>
                            <div class="simplebar-track simplebar-vertical" style="visibility: hidden;">
                                <div class="simplebar-scrollbar" style="height: 0px; display: none;"></div>
                            </div>
                        </div>
                        <div class="card-footer text-center border-top"><a class="card-link d-block"
                                href="app/social/notifications.html">View all</a></div>
                    </div>
                </div>
            </li>
            <li class="nav-item dropdown"><a class="nav-link pe-0 ps-2" id="navbarDropdownUser" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="avatar avatar-xl">
                        <img class="rounded-circle"
                            src="{{ asset(Auth::user()->profile_pic ? Auth::user()->profile_pic : 'assets/img/team/avatar.png') }}"
                            alt="" />
                    </div>
                </a>
                <div class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end py-0"
                    aria-labelledby="navbarDropdownUser">
                    <div class="bg-white dark__bg-1000 rounded-2 py-2">
                        <!-- <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#profileModal"><span
                                class="bi bi-person me-1"></span><span>Profile</span></a>
                        <div class="dropdown-divider"></div> -->
                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal"
                            data-bs-target="#exampleModal"><span class="bi bi-lock me-1"></span><span>Change
                                Password</span></a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('logout') }}"><span
                                class="bi bi-box-arrow-right me-1"></span> <span>Logout</span></a>
                    </div>
                </div>
            </li>
        </ul>
    </nav>
    <nav class="navbar navbar-light navbar-glass navbar-top navbar-expand-lg" style="display: none;"
        data-move-target="#navbarVerticalNav" data-navbar-top="combo">
        <button class="btn navbar-toggler-humburger-icon navbar-toggler me-1 me-sm-3" type="button"
            data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse"
            aria-expanded="false" aria-label="Toggle Navigation"><span class="navbar-toggle-icon"><span
                    class="toggle-line"></span></span></button>
        <a class="navbar-brand me-1 me-sm-3" href="{{ route('dashboard') }}">
            <div class="d-flex align-items-center"><img class="me-2"
                    src="/assets/img/icons/spot-illustrations/wwd.png" alt="" width="40" /><span
                    class="font-sans-serif text-primary"></span></div>
        </a>

        <ul class="navbar-nav navbar-nav-icons ms-auto flex-row align-items-center">
            <li class="nav-item dropdown"><a class="nav-link pe-0 ps-2" id="navbarDropdownUser" role="button"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="avatar avatar-xl">
                        <img class="rounded-circle" src="/assets/img/team/avatar.png" alt="" />
                    </div>
                </a>
                <div class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end py-0"
                    aria-labelledby="navbarDropdownUser">
                    <div class="bg-white dark__bg-1000 rounded-2 py-2">
                        <a class="dropdown-item fw-bold text-warning" href="#!"><span
                                class="fas fa-crown me-1"></span><span>Profile</span></a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('logout') }}">Logout</a>
                    </div>
                </div>
            </li>
        </ul>
    </nav>
    <!-- Change password modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="changePasswordForm" method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="form-group">
                                    <label for="channel_name">Old Password</label>
                                    <input class="form-control" type="password" name="old_password">
                                    <span id="old_password_error" class="text-danger"></span>
                                    <span id="old_password_match_error" class="text-danger"></span>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <div class="form-group">
                                    <label for="category">New Password</label>
                                    <input class="form-control" type="password" name="password">
                                    <span id="password_error" class="text-danger"></span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="category">Confirm Password</label>
                                    <input class="form-control" type="password" name="password_confirmation">
                                    <span id="password_confirmation_error" class="text-danger"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Profile modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profileModalLabel">Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('update_profile') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <p class="fs-10">{{ Auth::user()->first_name ? Auth::user()->first_name : '' }}</p>
                                    <input class="form-control"
                                        value="{{ Auth::user()->first_name ? Auth::user()->first_name : '' }}" type="hidden"
                                        name="first_name">
                                    <input class="form-control"
                                        value="{{ Auth::user()->last_name ? Auth::user()->last_name : '' }}" type="hidden"
                                        name="last_name">
                                    <span id="name_error" class="text-danger"></span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <p class="fs-10">{{ Auth::user()->email ? Auth::user()->email : '' }}</p>
                                    <input class="form-control"
                                        value="{{ Auth::user()->email ? Auth::user()->email : '' }}" type="hidden"
                                        name="email" readonly>
                                    <span id="email_error" class="text-danger"></span>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Profile Picture</label>
                                    <div class="profile-upload-wrapper">
                                        <input type="file" name="profile_pic" id="profile_pic" accept="image/*"
                                            hidden>

                                        <div class="profile-preview" id="profile_preview">
                                            @if (Auth::user()->profile_pic)
                                            <img src="{{ asset(Auth::user()->profile_pic) }}"
                                                alt="Profile Image">
                                            @else
                                            <span>Select Image</span>
                                            @endif
                                        </div>

                                        <button type="button" class="remove-btn d-none text-danger"
                                            id="removeImage">×</button>
                                    </div>
                                    <span id="profile_pic_error" class="text-danger"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>