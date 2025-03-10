<footer>
    <style>
        /* Ensure the footer stays at the bottom when needed */
       /* Make sure body takes the full height */
html, body {
    height: 100%;
    margin: 0;
    display: flex;
    flex-direction: column;
}

/* Content should expand to push the footer down// neo */
.content-wrapper {
    flex: 1;
}

/* Footer always sticks at the bottom */
footer {
    text-align: center;
    padding: 10px;
    background: black;
    color: white;
    position: relative; /* Change from fixed to relative */
    width: 100%;
    bottom: 0;}

    </style>
    <p>© 2025 FlexiFit Gym. All Rights Reserved.</p>
</footer>
