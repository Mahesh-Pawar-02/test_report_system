<?php
require_once 'config.php';
function generateSqlQuery($userQuestion, $databaseSchema, $apiKey, $apiUrl) {
    // Data to send to the API (same as the JSON in your curl command)
    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $databaseSchema . $userQuestion
                    ]
                ]
            ]
        ]
    ];
    $jsonData = json_encode($data);
    try {
        // Initialize cURL session
        $ch = curl_init($apiUrl);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response
        curl_setopt($ch, CURLOPT_POST, true);           // Use POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Set the JSON data
        curl_setopt($ch, CURLOPT_HTTPHEADER, [          // Set headers
            'Content-Type: application/json'
        ]);

        // Execute the request
        $response = curl_exec($ch);
    
        // Error handling
        if (curl_errno($ch)) {
            echo 'cURL Error: ' . curl_error($ch);
        } else {
            // Decode the JSON response
            $result = json_decode($response, true);
    
            // Process and display the result (adjust based on the actual response structure)
            if ($result && isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                // echo '<div class="ai-response">' . $result['candidates'][0]['content']['parts'][0]['text'] . '</div>';
                $sqlQuery = $result['candidates'][0]['content']['parts'][0]['text'];
                return $sqlQuery;    
            } else {
                echo 'Error: Could not process the API response.';
                echo '<pre>' . print_r($result, true) . '</pre>'; // For debugging, inspect the response
            }
        }
    
        // Close cURL session
        curl_close($ch);
    
    } catch (Exception $e) {
            $error = 'Error processing query: ' . $e->getMessage();
    }
}

function cleanSqlQuery($sqlWithPotentialJunk) {
     //  This regex is designed to be more resilient to variations in AI output.
     $pattern = '/\b(?:sql)?\s*(`{3})?\s*(SELECT.*?)(?:`{3})?\s*;?\s*\z/is';
     if (preg_match($pattern, $sqlWithPotentialJunk, $matches)) {
         return trim($matches[2]); //  Return the captured SQL (group 2)
     } else {
         return 'ERROR: Could not extract SQL from AI response.';
     }
}

function executeSqlQuery($conn, $sqlQuery) {
    try {
        $stmt = $conn->prepare($sqlQuery);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch results as associative array
        return $results ? $results : [];
    } catch (PDOException $e) {
        return 'ERROR: Database error - ' . $e->getMessage();
    }
}

function getNaturalLanguageResponse($userQuestion, $sqlQuery, $results, $apiKey, $apiUrl) {
    if (empty($results)) {
        return "No results were found.";
    }

    $prompt = "Translate these database results into a natural language response to the question: \"$userQuestion\".\n";
    $prompt .= "SQL Query: $sqlQuery\n";
    $prompt .= "Results:\n" . json_encode($results) . "\n";
    $prompt .= "Keep the response concise and informative.";

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ];

    $jsonData = json_encode($data);

    $ch = curl_init($apiUrl);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response
    curl_setopt($ch, CURLOPT_POST, true);           // Use POST method
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Set the JSON data
    curl_setopt($ch, CURLOPT_HTTPHEADER, [          // Set headers
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return 'ERROR: API error - ' . curl_error($ch);
    } else {
        $result = json_decode($response, true);
        //  Adjust this based on the actual Gemini API response structure!
        if ($result && isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($result['candidates'][0]['content']['parts'][0]['text']);
        } elseif ($result && isset($result['error'])) {
            echo 'ERROR: Gemini API Error: ' . $result['error']['message'];
            return 'ERROR: Gemini API Error: ' . $result['error']['message'];
        } else {
            echo 'ERROR: Could not understand API response for natural language.';
            return 'ERROR: Could not understand API response for natural language.';
        }
    }
    curl_close($ch);
}

function displayResults($results, $userQuestion, $sqlQuery, $apiKey, $apiUrl) {
    if (is_string($results)) {
        echo "<p>" . htmlspecialchars($results) . "</p>"; // Display the error message
        return;
    }

    if (empty($results)) {
        echo "<p>No results found.</p>";
        return;
    }

    //  Attempt natural language generation
    $naturalLanguageResponse = getNaturalLanguageResponse($userQuestion, $sqlQuery, $results, $apiKey, $apiUrl);
    if (strpos(strtolower((string)$naturalLanguageResponse), 'error') === false) {
        echo "<h3><b>" . htmlspecialchars($naturalLanguageResponse) . "</b></h3>";
    } else {
        //  If AI fails, fall back to table display (or other simpler method)
        echo "<p>AI could not generate a natural language response. Displaying raw data:</p>";
        echo "<table><tr>";
        if (!empty($results)) {
            foreach (array_keys($results[0]) as $column) {
                echo "<th>" . htmlspecialchars($column) . "</th>";
            }
            echo "</tr>";
            foreach ($results as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
}

?>