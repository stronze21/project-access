@include('errors.layout', [
    'code' => 503,
    'label' => 'BHWIS connection unavailable',
    'title' => 'The local BHWIS server is offline',
    'message' => 'ACCESS is online, but it cannot reach the integrated BHWIS local server right now. Features that do not require BHWIS may continue to work normally.',
    'guidance' => [
        'Wait a moment, then try the request again.',
        'If the issue continues, ask the BHWIS server administrator to check the local computer, network connection, and ODBC service.',
        'Your ACCESS account and saved local records are not affected by this temporary connection problem.',
    ],
    'actionUrl' => $retryUrl,
    'actionLabel' => 'Try BHWIS again',
    'statusText' => 'BHWIS offline',
    'footer' => 'BHWIS connection error · Contact the local BHWIS or ACCESS administrator if service does not return.',
])
