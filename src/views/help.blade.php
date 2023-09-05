@extends('msdev2::layout.master')
@section('content')
<span class="toc-block" id="home">
    <header>
        <h1>Help & Support</h1>
        <h2>Need help with the app?</h2>
    </header>
    <section>
        <div class="column">
            <article>
                <div class="card columns twelve">
                    <h2>Contact our support</h2>
                    <p>Need Assistance? Our Live Chat Support is ready to assist you in real-time. Connect with us now and experience speedy solutions to all your questions and concerns!</p>
                </div>
            </article>
        </div>
    </section>
    <section>
        <div class="column">
            <article>
                <div class="alert">
                    <dl>
                      <dt>General Alert with Button</dt>
                      <dd>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis leo purus, rhoncus id ultrices vitae. <a href="#">Learn More</a></dd>
                    </dl>
                    <button control-id="ControlID-55">Change</button>
                </div>
            </article>
        </div>
    </section>
    <section>
        <div class="column">
            <article>
                <div class="card columns four">
                    <img src="images/css.svg">
                    <h5>Live Char</h5>

                    <p>Get just the stylesheet to quickly add within your project.</p>

                    <p><a href="css/uptown.css" class="button download" target="_blank">Download CSS</a></p>
                </div>
                <div class="card columns four">
                    <img src="images/source.svg">
                    <h5>Submit Ticket</h5>

                    <p>Get the SASS files to customize the stylesheet.</p>

                    <p><a href="https://github.com/shoppad/uptowncss/archive/master.zip" class="button download" target="_blank">Download Source</a></p>
                </div>
                <div class="card columns four">
                    <img src="images/github.svg">
                    <h5>Contact Us</h5>

                    <p>Learn more about Uptown CSS or help contribute to the cause.</p>

                    <p><a href="https://github.com/shoppad/uptowncss/" class="button secondary" target="_blank">View Project</a></p>
                </div>
            </article>
        </div>
    </section>

</span>
@endsection