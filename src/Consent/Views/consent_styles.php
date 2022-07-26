<!-- Consent -->
<style>
    #consent-close:checked + #consent-popup{display:none;}
    #consent-popup {
        position:fixed;
        top:30px;
        right:50px;
        width:450px;
        background-color: cadetblue;
        border: 1px solid lightgray;
        padding:20px;
        z-index:2;
        font-size: 0.85rem;
        line-height: 1.2;
    }
    #consent-popup a {
        text-decoration: none;
    }
    #consent-popup a:hover {
        text-decoration: underline;
    }
    #consent-popup h1{
        font-size:1.2em;
    }
    #consent-popup h1:before{
        content:"";
        padding:0;
    }
    #consent-popup #consent-close {
        position:absolute;
        top:20px;right:20px;
        cursor:pointer;
        font-size:1.2em;
        font-weight: bolder;
        color: #252535;
    }
    #consent-popup #consent-close:hover {
        color: whitesmoke;
    }
    #consent-popup #consent-ok {
        cursor:pointer;
        font-size:1.3em;
        padding:10px 20px;
        font-weight:700;
        color:white;
    }
    #consent-popup a#consent-reject-all,
    #consent-popup a#consent-accept-all,
    #consent-popup a#consent-accept-custom {
        display: inline-block;
        width: 49%;
        padding: 0.5em 1em;
        text-align: center;
    }
    #consent-popup a#consent-reject-all {
        /*margin-right: 1%;*/
        background-color: #252535;
        color: whitesmoke;
    }
    #consent-popup a#consent-reject-all:hover ,
    #consent-popup a#consent-accept-all:hover {
        box-shadow: 0  0 5px 5px rgba(0,0,0,0.1);
    }
    #consent-popup a#consent-accept-all,
    #consent-popup a#consent-accept-custom {
        background-color: whitesmoke;
        color: #252535;
    }

    #consent-popup .customize-btn {
        text-align: center;
        position: relative;
        margin-top: 1rem;
    }

    #consent-popup .customize-btn a {
        margin: 0 auto;
        width: auto;
        color: #252535;
    }

    #consent-popup #consents tr td:first-child {
        padding-right: 1rem;
    }
    #consent-popup .consent-title {
        margin-bottom: 0;
    }

    #consent-popup .show-simple {
        display: block;
    }
    #consent-popup .show-simple > div {
        display: flex;
        align-items: center;
        flex-direction: column;
        gap: 0.5rem;
    }
    #consent-popup.custom .show-simple {
        display: none;
    }
    #consent-popup .show-custom {
        display: none;
    }
    #consent-popup.custom .show-custom {
        display: block;
    }
</style>
