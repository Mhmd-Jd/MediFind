<?php
function hasUserRated($user_id, $booking_id, $conn)
{
    $query = "SELECT rating_id FROM ratings WHERE user_id = ? AND booking_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}