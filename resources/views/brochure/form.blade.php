<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Generate Product Brochure</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <link rel="stylesheet" href="{{ asset('/updated/custom/font.css') }}">
    <link rel="stylesheet" href="{{ asset('/updated/icons/font-awesome.min.css') }}">
    {{--  <!-- Font Awesome Icons -->  --}}
    <link rel="stylesheet" href="{{ asset('/updated/plugins/fontawesome-free/css/all.min.css') }}">
    {{--  <!-- Ekko Lightbox -->  --}}
    <link rel="stylesheet" href="{{ asset('/updated/plugins/ekko-lightbox/ekko-lightbox.css') }}">
    {{--  <!-- Theme style -->  --}}
    <link rel="stylesheet" href="{{ asset('/updated/dist/css/adminlte.min.css') }}">
    
    <style>
      @font-face { font-family: 'Poppins'; src: url({{ asset('font/Poppins/Poppins-Regular.ttf') }}); } 
      * {
        box-sizing: border-box;
        margin: 0;
        font-family: "Poppins";
      }
      body {
        background-color: #F5F8FD;
      }
      .custom-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 90vh;
        padding: 2rem;
      }
      .custom-card {
        background-color: #FFF;
        width: 100%;
        max-width: 500px;
        border-radius: 0.5rem;
        box-shadow: 0px 5px 20px rgba(49, 104, 146, 0.25);
      }
      .custom-card .custom-card-body {
        padding: 2.5rem 1.25rem 2.2rem 1.25rem;
      }
      .custom-card .custom-card-body .custom-card-title {
        color: #1689ff;
        font-size: 1.25rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 0.25rem;
      }
      .custom-card .custom-card-body .custom-card-subtitle {
        color: #777;
        font-weight: 500;
        text-align: center;
      }
      .file-upload {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 4rem 1.5rem;
        margin-top: 2rem;
        border: 3px dashed #9dceff;
        border-radius: 0.5rem;
        transition: background-color 0.25s ease-out;
      }
      .file-upload:hover {
        background-color: #dbedff;
      }
      .file-upload .file-input {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        outline: none;
        cursor: pointer;
      }
      .icon {
        width: 75px;
        margin-bottom: 1rem;
      }
      @media (max-width: 600px) {
        .icon {
          width: 50px;
        }
      }
    </style>
  </head>
  <body>
    <div class="row m-0 p-0">
      <div class="col-10">
        @if (session()->has('error'))
          <div class="mx-auto" style="text-align: center; color: #CE1E09; font-size: 12pt; margin: 8px;">
            {{ session()->get('error') }}
          </div>
        @endif
        <div
          id="brochure-form-app"
          data-csrf="{{ csrf_token() }}"
          data-template-url="{{ Storage::disk('upcloud')->url('templates/AthenaERP - Brochure-Import-Template.xlsx') }}"
        ></div>
      </div>


      <div class="recent-sidebar">
        <div id="brochure-sidebar"></div>
      </div>

<style>
  .recent-sidebar {
    position: fixed;
    width: min(400px, 92vw);
    max-width: 400px;
    top:0;
    right: 0;
    bottom: 0;
    background: #fff;
    border-left: 1px solid  #abb2b9 ;
    overflow-y: auto;
    overflow-x: auto;
  }
  @media (max-width: 767.98px) {
    .recent-sidebar { width: 100%; max-width: 100%; }
    .col-10 { flex: 0 0 100%; max-width: 100%; }
  }
</style>



    </div>

    @vite(['resources/js/brochure.js'])
    <script src="{{ asset('/updated/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('/updated/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  </body>
</html>
    